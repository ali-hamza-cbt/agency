<?php

namespace App\Http\Controllers\Api\Web;

use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\PaginatesOrAll;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SalesmanController extends Controller
{
    use PaginatesOrAll;
    protected $user;

    public function __construct()
    {
        $this->user = currentAccount(); // agency or super admin
    }
    /**
     * List all salesmen under the logged-in agency
     */
    public function index(Request $request)
    {
        $agencyId = $this->user->id;

        // Start query builder
        $query = User::query()->where('agency_id', $agencyId)->with('salesmanProfile')->where('role', 'salesman');

        // Apply search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('salesmanProfile', function ($sub) use ($search) {
                        $sub->where('phone', 'like', "%{$search}%")
                            ->orWhere('cnic', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%")
                            ->orWhere('vehicle_number', 'like', "%{$search}%")
                            ->orWhere('vehicle_type', 'like', "%{$search}%");
                    });
            });
        }


        // Pass the Builder to paginateOrAll
        $salesmen = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($salesmen, 'Salesmen retrieved successfully.');
    }
    /**
     * Store a new salesman
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // User fields
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',

            // Salesman profile fields
            'employee_code'  => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'cnic'           => 'nullable|string|max:20',
            'vehicle_number' => 'nullable|string|max:50',
            'vehicle_type'   => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        try {

            DB::beginTransaction();

            $user = User::create([
                'agency_id' => $this->user->id,
                'role'      => 'salesman',
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => bcrypt($request->phone),
            ]);

            $user->salesmanProfile()->create([
                'employee_code'  => $request->employee_code,
                'phone'          => $request->phone,
                'cnic'           => $request->cnic,
                'vehicle_number' => $request->vehicle_number,
                'vehicle_type'   => $request->vehicle_type,
            ]);

            DB::commit();

            return ApiResponse::success($user->load('salesmanProfile'), 'Salesman created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
