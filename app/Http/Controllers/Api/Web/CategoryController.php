<?php

namespace App\Http\Controllers\Api\Web;

use App\Models\Category;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\PaginatesOrAll;
use Illuminate\Validation\Rule;
use App\Helpers\ImageStorageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use PaginatesOrAll;
    protected $user;

    public function __construct()
    {
        $this->user = currentAccount(); // agency or super admin
    }

    public function index(Request $request)
    {
        $query = Category::query()->where('agency_id', $this->user->id);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        $categories = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($categories, 'Categories fetched successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => [
                'required',
                Rule::exists('brands', 'id')->where(function ($query) {
                    $query->where('agency_id', $this->user->id);
                }),
            ],

            'name'        => 'required|string|max:255',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        try {
            $data = $request->only(['brand_id', 'name', 'description']);
            $data['agency_id'] = $this->user->id;

            if ($request->hasFile('logo')) {
                $data['logo'] = ImageStorageHelper::store($request->file('logo'), 'category-logo');
            }

            $category = Category::create($data);

            return ApiResponse::success($category, 'Category created successfully.');
        } catch (\Throwable $e) {
            return ApiResponse::error([], 'Failed to create category.');
        }
    }

    public function show($id)
    {
        $category = Category::where('agency_id', $this->user->id)->find($id);

        if (!$category) {
            return ApiResponse::error('Category not found.');
        }

        return ApiResponse::success($category, 'Category details fetched.');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'brand_id'    => [
                'required',
                Rule::exists('brands', 'id')->where(function ($query) {
                    $query->where('agency_id', $this->user->id);
                }),
            ],
            'name'        => 'required|string|max:255',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        try {
            $category = Category::where('id', $id)->where('agency_id', $this->user->id)->first();

            if (!$category) {
                return ApiResponse::error('Category not found.');
            }

            $data = $request->only(['brand_id', 'name', 'description']);

            if ($request->hasFile('logo')) {
                $data['logo'] = ImageStorageHelper::update($request->file('logo'), 'category-logo', $category->logo);
            }

            $category->update($data);

            return ApiResponse::success($category, 'Category updated successfully.');
        } catch (\Throwable $e) {
            return ApiResponse::error([], 'Failed to update category.');
        }
    }


    public function destroy($id)
    {
        $category = Category::where('agency_id', $this->user->id)->find($id);

        if (!$category) {
            return ApiResponse::error('Category not found.');
        }

        $category->delete();

        return ApiResponse::success([], 'Category deleted successfully.');
    }

    public function changeStatus(Request $request, $id)
    {
        $category = Category::where('agency_id', $this->user->id)->find($id);

        if (!$category) {
            return ApiResponse::error('Category not found.');
        }

        $status = $request->input('status');

        if (!in_array($status, ['active', 'inactive'])) {
            return ApiResponse::validationError(['status' => ['Status must be active or inactive']], 'Invalid status value.');
        }

        $category->status = $status;
        $category->save();

        return ApiResponse::success($category, 'Category status updated successfully.');
    }

    public function trashed(Request $request)
    {
        $query = Category::onlyTrashed()->where('agency_id', $this->user->id);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        $categories = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($categories, 'Trashed categories fetched successfully.');
    }

    public function restore($id)
    {
        $category = Category::onlyTrashed()->where('agency_id', $this->user->id)->find($id);

        if (!$category) {
            return ApiResponse::error('Category not found in trash.');
        }

        $category->restore();

        return ApiResponse::success($category, 'Category restored successfully.');
    }

    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->where('agency_id', $this->user->id)->find($id);

        if (!$category) {
            return ApiResponse::error('Category not found in trash.');
        }

        $category->forceDelete();

        return ApiResponse::success([], 'Category permanently deleted.');
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        Category::where('agency_id', $this->user->id)->whereIn('id', $request->ids)->delete();

        return ApiResponse::success([], 'Selected categories deleted.');
    }

    public function bulkRestore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        Category::onlyTrashed()->where('agency_id', $this->user->id)->whereIn('id', $request->ids)->restore();

        return ApiResponse::success([], 'Selected categories restored.');
    }
}
