<?php

namespace App\Http\Controllers\Api\Web;

use App\Models\Brand;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Helpers\ImageStorageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    
    protected $user;

    public function __construct()
    {
        $this->user = currentAccount(); // agency or super admin
    }

    public function index(Request $request)
    {
        $query = Brand::query()->where('agency_id', $this->user->id);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $perPage = is_numeric($request->input('paginate')) ? min((int) $request->input('paginate'), 100) : 10;

        $brands = $query->latest()->paginate($perPage);

        return ApiResponse::success($brands, 'Brands fetched successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        try {
            $data = $request->only(['name', 'description']);
            $data['agency_id'] = $this->user->id;

            if ($request->hasFile('logo')) {
                $data['logo'] = ImageStorageHelper::store($request->file('logo'), 'brand-logo');
            }

            $brand = Brand::create($data);

            return ApiResponse::success($brand, 'Brand created successfully.');
        } catch (\Throwable $e) {
            return ApiResponse::error([], 'Failed to create brand.');
        }
    }

    public function show($id)
    {
        $brand = Brand::where('agency_id', $this->user->id)->find($id);

        if (!$brand) {
            return ApiResponse::error([], 'Brand not found.');
        }

        return ApiResponse::success($brand, 'Brand details fetched.');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        try {

            $brand = Brand::where('id', $id)->where('agency_id', $this->user->id)->first();

            if (!$brand) {
                return ApiResponse::error('Brand not found.');
            }

            $data = $request->only(['name', 'description']);

            if ($request->hasFile('logo')) {
                $data['logo'] = ImageStorageHelper::update($request->file('logo'), 'brand-logo', $brand->logo);
            }

            $brand->update($data);

            return ApiResponse::success($brand, 'Brand updated successfully.');
        } catch (\Throwable $e) {
            return ApiResponse::error([], 'Failed to update brand.');
        }
    }


    public function delete($id)
    {
        $brand = Brand::where('agency_id', $this->user->id)->find($id);

        if (!$brand) {
            return ApiResponse::error([], 'Brand not found.');
        }

        $brand->delete();

        return ApiResponse::success([], 'Brand deleted successfully.');
    }

    public function changeStatus(Request $request, $id)
    {
        $brand = Brand::where('agency_id', $this->user->id)->find($id);

        if (!$brand) {
            return ApiResponse::error([], 'Brand not found.', 404);
        }

        $status = $request->input('status');

        if (!in_array($status, ['active', 'inactive'])) {
            return ApiResponse::validationError(['status' => ['Status must be active or inactive']], 'Invalid status value.');
        }

        $brand->status = $status;
        $brand->save();

        return ApiResponse::success($brand, 'Brand status updated successfully.');
    }


    public function trashed(Request $request)
    {
        $query = Brand::query()->onlyTrashed()->where('agency_id', $this->user->id);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $perPage = is_numeric($request->input('paginate')) ? min((int) $request->input('paginate'), 100) : 10;

        $brands = $query->latest()->paginate($perPage);

        return ApiResponse::success($brands, 'Trashed brands fetched successfully.');
    }

    public function restore($id)
    {
        $brand = Brand::onlyTrashed()->where('agency_id', $this->user->id)->find($id);

        if (!$brand) {
            return ApiResponse::error([], 'Brand not found in trash.');
        }

        $brand->restore();

        return ApiResponse::success($brand, 'Brand restored successfully.');
    }

    public function forceDelete($id)
    {
        $brand = Brand::onlyTrashed()->where('agency_id', $this->user->id)->find($id);

        if (!$brand) {
            return ApiResponse::error([], 'Brand not found in trash.');
        }

        $brand->forceDelete();

        return ApiResponse::success([], 'Brand permanently deleted.');
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        Brand::where('agency_id', $this->user->id)->whereIn('id', $request->ids)->delete();

        return ApiResponse::success([], 'Selected brands deleted.');
    }

    public function bulkRestore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        Brand::onlyTrashed()->where('agency_id', $this->user->id)->whereIn('id', $request->ids)->restore();

        return ApiResponse::success([], 'Selected brands restored.');
    }
}
