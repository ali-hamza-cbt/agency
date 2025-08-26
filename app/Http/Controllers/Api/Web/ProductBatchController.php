<?php

namespace App\Http\Controllers\Api\Web;

use App\Helpers\ApiResponse;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use App\Traits\PaginatesOrAll;
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
            'product_id' => 'required|exists:products,id',
            'batch_no' => 'required|string|max:255',
            'expiry_date' => 'required|date|after:today',
            'pack_type' => 'required|string|max:50',
            'pack_size' => 'required|integer|min:1',
            'pack_qty' => 'required|integer|min:0',
            'single_qty' => 'nullable|integer|min:0',
            'reserved_qty' => 'nullable|integer|min:0',
            'damaged_qty' => 'nullable|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'mrp_per_unit' => 'nullable|numeric|min:0',
            'warehouse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) return ApiResponse::validationError($validator->errors());

        $data = $request->all();
        $data['agency_id'] = $this->user->id;

        // Calculate single_qty if not provided
        if (!isset($data['single_qty'])) {
            $data['single_qty'] = $data['pack_size'] * $data['pack_qty'];
        }

        $batch = ProductBatch::create($data);

        return ApiResponse::success($batch, 'Product batch created successfully.');
    }

    // Show batch details
    public function show($id)
    {
        $batch = ProductBatch::with('product')->where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error([], 'Product batch not found.');

        return ApiResponse::success($batch, 'Product batch details fetched successfully.');
    }

    // Update batch
    public function update(Request $request, $id)
    {
        $batch = ProductBatch::where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error([], 'Product batch not found.');

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'batch_no' => 'required|string|max:255',
            'expiry_date' => 'required|date|after:today',
            'pack_type' => 'required|string|max:50',
            'pack_size' => 'required|integer|min:1',
            'pack_qty' => 'required|integer|min:0',
            'single_qty' => 'nullable|integer|min:0',
            'reserved_qty' => 'nullable|integer|min:0',
            'damaged_qty' => 'nullable|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'mrp_per_unit' => 'nullable|numeric|min:0',
            'warehouse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) return ApiResponse::validationError($validator->errors());

        $data = $request->all();
        if (!isset($data['single_qty'])) {
            $data['single_qty'] = $data['pack_size'] * $data['pack_qty'];
        }

        $batch->update($data);

        return ApiResponse::success($batch, 'Product batch updated successfully.');
    }

    // Soft delete batch
    public function delete($id)
    {
        $batch = ProductBatch::where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error([], 'Product batch not found.');

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
        if (!$batch) return ApiResponse::error([], 'Product batch not found in trash.');

        $batch->restore();

        return ApiResponse::success($batch, 'Product batch restored successfully.');
    }

    // Force delete batch
    public function forceDelete($id)
    {
        $batch = ProductBatch::onlyTrashed()->where('agency_id', $this->user->id)->find($id);
        if (!$batch) return ApiResponse::error([], 'Product batch not found in trash.');

        $batch->forceDelete();

        return ApiResponse::success([], 'Product batch permanently deleted.');
    }
}
