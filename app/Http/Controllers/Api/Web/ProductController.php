<?php

namespace App\Http\Controllers\Api\Web;

use App\Models\Product;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\PaginatesOrAll;
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
        $query = Product::query()->where('agency_id', $this->user->id);

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
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'container_type' => 'required|string',
            'size_ml' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'reorder_level' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $data = $request->only([
            'brand_id',
            'category_id',
            'name',
            'sku',
            'container_type',
            'size_ml',
            'description',
            'reorder_level',
            'barcode',
            'status'
        ]);
        $data['agency_id'] = $this->user->id;

        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = ImageStorageHelper::store($image, 'product-images');
            }
            $data['images'] = json_encode($images);
        }

        $product = Product::create($data);

        return ApiResponse::success($product, 'Product created successfully.');
    }

    public function show($id)
    {
        $product = Product::where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error([], 'Product not found.');

        return ApiResponse::success($product, 'Product details fetched.');
    }

    public function update(Request $request, $id)
    {
        $product = Product::where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error([], 'Product not found.');

        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $id,
            'container_type' => 'required|string',
            'size_ml' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'reorder_level' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) return ApiResponse::validationError($validator->errors());

        $data = $request->only([
            'brand_id',
            'category_id',
            'name',
            'sku',
            'container_type',
            'size_ml',
            'description',
            'reorder_level',
            'barcode',
            'status'
        ]);

        // Handle multiple images
        if ($request->hasFile('images')) {
            if ($product->images) {
                ImageStorageHelper::deleteMultiple(json_decode($product->images, true));
            }
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = ImageStorageHelper::store($image, 'product-images');
            }
            $data['images'] = json_encode($images);
        }

        $product->update($data);

        return ApiResponse::success($product, 'Product updated successfully.');
    }

    public function delete($id)
    {
        $product = Product::where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error([], 'Product not found.');

        $product->delete();
        return ApiResponse::success([], 'Product deleted successfully.');
    }

    public function changeStatus(Request $request, $id)
    {
        $product = Product::where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error([], 'Product not found.');

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
        $query = Product::onlyTrashed()->where('agency_id', $this->user->id);
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }
        $products = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($products, 'Trashed products fetched.');
    }

    public function restore($id)
    {
        $product = Product::onlyTrashed()->where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error([], 'Product not found in trash.');

        $product->restore();
        return ApiResponse::success($product, 'Product restored successfully.');
    }

    public function forceDelete($id)
    {
        $product = Product::onlyTrashed()->where('agency_id', $this->user->id)->find($id);
        if (!$product) return ApiResponse::error([], 'Product not found in trash.');

        if ($product->images) {
            ImageStorageHelper::deleteMultiple(json_decode($product->images, true));
        }

        $product->forceDelete();
        return ApiResponse::success([], 'Product permanently deleted.');
    }
}
