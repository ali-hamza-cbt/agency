<?php

namespace App\Http\Controllers\Api\Web;

use App\Helpers\ApiResponse;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use App\Traits\PaginatesOrAll;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductBatchController extends Controller
{
    use PaginatesOrAll;

    protected $user;

    public function __construct()
    {
        $this->user = currentAccount(); // agency or super admin
    }

    // List product batches
    public function index(Request $request)
    {
        $query = ProductBatch::query()->with('product')->where('agency_id', $this->user->id);

        if ($search = $request->input('search')) {
            $query->whereHas('product', fn($q) => $q->where('name', 'like', "%$search%"));
        }

        $batches = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($batches, 'Product batches fetched successfully.');
    }

    // Create new batch
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('agency_id', $this->user->id);
                }),
            ],
            'expiry_date' => 'required|date|after:today',
            'pack_type'      => 'required|string|max:50',
            'pack_size'      => 'required|integer|min:1',
            'pack_qty'       => 'required|integer|min:0',
            'damaged_qty'    => 'nullable|integer|min:0',
            'cost_price'     => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'mrp_per_unit'   => 'nullable|numeric|min:0',
            'warehouse'      => 'nullable|string|max:255',

        ]);

        if ($validator->fails()) return ApiResponse::validationError($validator->errors());

        $data = $request->all();
        $data['agency_id'] = $this->user->id;

        // Calculate single_qty if not provided
        $data['single_qty'] = $data['pack_size'] * $data['pack_qty'];

        // Auto calculate profit margin
        $data['mrp_per_unit'] = $data['price_per_unit'] - $data['cost_price'];

        $batch = ProductBatch::create($data);

        return ApiResponse::success($batch, 'Product batch created successfully.');
    }

    // Show batch details
    public function show($id)
    {
        $batch = ProductBatch::with('product')->where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error('Product batch not found.');

        return ApiResponse::success($batch, 'Product batch details fetched successfully.');
    }

    // Update batch
    public function update(Request $request, $id)
    {
        $batch = ProductBatch::where('agency_id', $this->user->id)->find($id);
        if (!$batch) {
            return ApiResponse::error('Product batch not found.');
        }

        $validator = Validator::make($request->all(), [
            'expiry_date'    => 'sometimes|required|date|after:today',
            'pack_type'      => 'sometimes|required|string|max:50',
            'pack_size'      => 'sometimes|required|integer|min:1',
            'pack_qty'       => 'sometimes|required|integer|min:0',
            'damaged_qty'    => 'nullable|integer|min:0',
            'cost_price'     => 'sometimes|required|numeric|min:0',
            'price_per_unit' => 'sometimes|required|numeric|min:0',
            'mrp_per_unit'   => 'nullable|numeric|min:0',
            'warehouse'      => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $data = $request->all();

        // Recalculate single_qty if pack_size or pack_qty changed
        if (isset($data['pack_size']) || isset($data['pack_qty'])) {
            $packSize = $data['pack_size'] ?? $batch->pack_size;
            $packQty  = $data['pack_qty'] ?? $batch->pack_qty;
            $data['single_qty'] = $packSize * $packQty;
        }

        // Recalculate profit margin whenever cost_price or price_per_unit changes
        $costPrice     = $data['cost_price'] ?? $batch->cost_price;
        $pricePerUnit  = $data['price_per_unit'] ?? $batch->price_per_unit;
        $data['mrp_per_unit'] = $pricePerUnit - $costPrice;

        // Protect fields that should never change
        unset($data['batch_no'], $data['agency_id'], $data['product_id']);

        $batch->update($data);

        return ApiResponse::success($batch, 'Product batch updated successfully.');
    }

    // Soft delete batch
    public function destroy($id)
    {
        $batch = ProductBatch::where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error('Product batch not found.');

        $batch->delete();

        return ApiResponse::success([], 'Product batch deleted successfully.');
    }

    // List trashed batches
    public function trashed(Request $request)
    {
        $query = ProductBatch::onlyTrashed()->with('product')->where('agency_id', $this->user->id);
        $batches = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($batches, 'Trashed product batches fetched successfully.');
    }

    // Restore soft deleted batch
    public function restore($id)
    {
        $batch = ProductBatch::onlyTrashed()->where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error('Product batch not found in trash.');

        $batch->restore();

        return ApiResponse::success($batch, 'Product batch restored successfully.');
    }

    // Force delete batch
    public function forceDelete($id)
    {
        $batch = ProductBatch::onlyTrashed()->where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error('Product batch not found in trash.');

        $batch->forceDelete();

        return ApiResponse::success([], 'Product batch permanently deleted.');
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

        ProductBatch::where('agency_id', $this->user->id)->whereIn('id', $request->ids)->delete();

        return ApiResponse::success([], 'Selected product batches deleted successfully.');
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

        ProductBatch::onlyTrashed()->where('agency_id', $this->user->id)->whereIn('id', $request->ids)->restore();

        return ApiResponse::success([], 'Selected product batches restored successfully.');
    }
}
