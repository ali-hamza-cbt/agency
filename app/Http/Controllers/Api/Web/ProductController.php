<?php

namespace App\Http\Controllers\Api\Web;

use App\Models\Product;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\PaginatesOrAll;
use Illuminate\Validation\Rule;
use App\Helpers\ImageStorageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use PaginatesOrAll;

    protected $user;

    public function __construct()
    {
        $this->user = currentAccount(); // agency or super admin
    }

    public function index(Request $request)
    {
        // Start query builder
        $query = Product::query()->where('agency_id', $this->user->id)->with(['brand', 'category', 'batches']);

        // Apply search
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        // Pass the Builder to paginateOrAll
        $products = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($products, 'Products fetched successfully.');
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
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) use ($request) {
                    $query->where('agency_id', $this->user->id)->where('brand_id', $request->brand_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'container_type' => 'required|string',
            'size_ml' => 'required|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,svg|max:2048',
            'reorder_level' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $data = $request->only([
            'brand_id',
            'category_id',
            'name',
            'container_type',
            'size_ml',
            'reorder_level',
            'description',
        ]);
        $data['agency_id'] = $this->user->id;

        // Step 1: Create product without images
        $product = Product::create($data);

        // Step 2: Handle image uploads (if any)
        if ($request->hasFile('images')) {
            $uploadedImages = [];
            foreach ($request->file('images') as $file) {
                $uploadedImages[] = ImageStorageHelper::store($file, "products/{$product->id}", 'public');
            }

            // Step 3: Update product with images
            $product->update(['images' => $uploadedImages]);
        }

        return ApiResponse::success($product, 'Product created successfully.');
    }

    public function show($id)
    {
        $product = Product::where('agency_id', $this->user->id)->with(['brand', 'category', 'batches'])->find($id);
        if (!$product) return ApiResponse::error([], 'Product not found.');

        return ApiResponse::success($product, 'Product details fetched.');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => [
                'required',
                Rule::exists('brands', 'id')->where(
                    fn($q) =>
                    $q->where('agency_id', $this->user->id)
                ),
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(
                    fn($q) =>
                    $q->where('agency_id', $this->user->id)->where('brand_id', $request->brand_id)
                ),
            ],
            'name'           => 'required|string|max:255',
            'container_type' => 'required|string',
            'size_ml'        => 'required|integer|min:1',
            'description'    => 'nullable|string',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpg,jpeg,png,svg|max:2048',
            'keep_images'    => 'nullable|array', // old images to keep
        ]);
        if ($validator->fails()) return ApiResponse::validationError($validator->errors());

        $product = Product::where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error('Product not found.');

        // Existing images from DB (JSON cast gives array)
        $existingImages = $product->images ?? [];
        $keepImages = array_filter($request->input('keep_images', []), fn($path) => !empty($path));
        $newImages = [];


        // Delete all old images except the ones to keep
        ImageStorageHelper::deleteAllExcept($keepImages, $existingImages);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $storedPath = ImageStorageHelper::store($file, "products/{$product->id}");

                if ($storedPath) { // only add if not null
                    $newImages[] = $storedPath;
                }
            }
        }

        // Final images = kept + new
        $finalImages = array_merge($keepImages, $newImages);

        // Update product
        $product->update([
            'brand_id'       => $request->brand_id,
            'category_id'    => $request->category_id,
            'name'           => $request->name,
            'container_type' => $request->container_type,
            'size_ml'        => $request->size_ml,
            'description'    => $request->description,
            'reorder_level'  => $request->reorder_level,
            'status'         => $request->status ?? $product->status,
            'images'         => $finalImages,
        ]);

        return ApiResponse::success($product, 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error('Product not found.');

        $product->delete();
        return ApiResponse::success([], 'Product deleted successfully.');
    }

    public function changeStatus(Request $request, $id)
    {
        $product = Product::where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error('Product not found.');

        $status = $request->input('status');
        if (!in_array($status, ['active', 'inactive'])) {
            return ApiResponse::validationError(['status' => ['Invalid status']]);
        }

        $product->status = $status;
        $product->save();

        return ApiResponse::success($product, 'Product status updated.');
    }

    public function trashed(Request $request)
    {
        $query = Product::onlyTrashed()->where('agency_id', $this->user->id)->with(['brand', 'category', 'batches']);
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }
        $products = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($products, 'Trashed products fetched.');
    }

    public function restore($id)
    {
        $product = Product::onlyTrashed()->where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error('Product not found in trash.');

        $product->restore();
        return ApiResponse::success($product, 'Product restored successfully.');
    }

    public function forceDelete($id)
    {
        $product = Product::onlyTrashed()->where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error('Product not found in trash.');

        if ($product->images) {
            ImageStorageHelper::deleteMultiple($product->images);
        }

        $product->forceDelete();
        return ApiResponse::success([], 'Product permanently deleted.');
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

        Product::where('agency_id', $this->user->id)->whereIn('id', $request->ids)->delete();

        return ApiResponse::success([], 'Selected products deleted.');
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

        Product::onlyTrashed()->where('agency_id', $this->user->id)->whereIn('id', $request->ids)->restore();

        return ApiResponse::success([], 'Selected products restored.');
    }
}
