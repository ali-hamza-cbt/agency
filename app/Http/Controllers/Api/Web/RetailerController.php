<?php

namespace App\Http\Controllers\Api\Web;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\PaginatesOrAll;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Retailer\SMSService;
use App\Services\Retailer\EmailService;
use Illuminate\Support\Facades\Validator;

class RetailerController extends Controller
{
    use PaginatesOrAll;

    protected $user;
    protected $smsService;
    protected $emailService;

    public function __construct(SMSService $smsService, EmailService $emailService)
    {
        $this->smsService = $smsService;
        $this->emailService = $emailService;

        $this->user = currentAccount(); // agency or super admin
    }

    /**
     * List retailers
     */
    public function index(Request $request)
    {
        $query = User::query()->where('agency_id', $this->user->id)->where('role', 'retailer')->orderByDesc('id')->with('retailerProfile');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $retailers = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($retailers, 'Retailers fetched successfully.');
    }


    /**
     * Create new retailer
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:9',
            'phone'    => 'required|string|max:20',
            'address'  => 'nullable|string',
            'city'     => 'nullable|string|max:100',
            'state'    => 'nullable|string|max:100',
            'zip'      => 'nullable|string|max:20',
            'country'  => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $data = $request->only(['name', 'email', 'password', 'phone', 'address', 'city', 'state', 'zip', 'country']);

            // Handle temp email
            if (empty($data['email'])) {
                $data['email'] = $this->generateTempEmail($data['name']);
                $isTemporaryEmail = true;
            } else {
                $isTemporaryEmail = false;
            }

            // Handle password
            if (empty($data['password'])) {
                $data['password'] = $this->generatePassword(8);
            }
            $plainPassword = $data['password'];
            $data['password'] = bcrypt($data['password']);

            $data['role'] = 'retailer';
            $data['agency_id'] = $this->user->id;

            // Create retailer + profile
            $retailer = User::create($data);
            $retailer->retailerProfile()->create([
                'shop_name' => $request->shop_name,
                'shop_address' => $request->shop_address,
                'phone' => $request->phone,
            ]);

            DB::commit();

            // ----------------------------
            // Notifications AFTER commit
            // ----------------------------
            $message = "Welcome to Our App!\n\n"
                . "Your login details:\n"
                . "Email: {$retailer->email}\n"
                . "Password: {$plainPassword}\n\n"
                . "Download the app: " . config('app.url') . "/download";

            try {
                $this->smsService->send($retailer->phone, $message);
            } catch (\Throwable $e) {
                \Log::error("SMS sending failed: " . $e->getMessage());
            }

            if (!$isTemporaryEmail) {
                try {
                    $this->emailService->sendCredentials(
                        $retailer->email,
                        $retailer->name,
                        $plainPassword
                    );
                } catch (\Throwable $e) {
                    \Log::error("Email sending failed: " . $e->getMessage());
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Retailer created successfully.',
                'data' => $retailer->load('retailerProfile')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => "Failed to create retailer: " . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show retailer details
     */
    public function show($id)
    {
        $retailer = User::where('agency_id', $this->user->id)->where('role', 'retailer')->with('retailerProfile')->find($id);

        if (!$retailer) {
            return ApiResponse::error('Retailer not found.');
        }

        return ApiResponse::success($retailer, 'Retailer details fetched.');
    }

    /**
     * Update retailer
     */
    public function update(Request $request, $id)
    {
        $retailer = User::where('agency_id', $this->user->id)
            ->where('role', 'retailer')
            ->find($id);

        if (!$retailer) {
            return ApiResponse::error('Retailer not found.');
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'email',
                Rule::unique('users')->ignore($retailer->id)
            ],
            'password' => 'nullable|string|min:6',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string',
            'city'     => 'nullable|string|max:100',
            'state'    => 'nullable|string|max:100',
            'zip'      => 'nullable|string|max:20',
            'country'  => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $data = $request->only([
            'name',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip',
            'country'
        ]);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $retailer->update($data);

        return ApiResponse::success($retailer->load('retailerProfile'), 'Retailer updated successfully.');
    }

    /**
     * Delete retailer (soft delete)
     */
    public function destroy($id)
    {
        $retailer = User::where('agency_id', $this->user->id)
            ->where('role', 'retailer')
            ->find($id);

        if (!$retailer) {
            return ApiResponse::error('Retailer not found.');
        }

        $retailer->delete();

        return ApiResponse::success([], 'Retailer deleted successfully.');
    }

    /**
     * Change Status
     */
    public function changeStatus(Request $request, $id)
    {
        $retailer = User::where('agency_id', $this->user->id)->where('role', 'retailer')->find($id);

        if (!$retailer) {
            return ApiResponse::error('Retailer not found.');
        }

        $status = $request->input('status');
        if (!in_array($status, ['active', 'inactive'])) {
            return ApiResponse::validationError(['status' => ['Invalid status']]);
        }

        $retailer->status = $status;
        $retailer->save();

        return ApiResponse::success($retailer->load('retailerProfile'), 'Retailer status updated.');
    }

    /**
     * Trashed Items
     */
    public function trashed(Request $request)
    {
        $query = User::onlyTrashed()
            ->where('agency_id', $this->user->id)
            ->where('role', 'retailer')
            ->with('retailerProfile');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $retailers = $this->paginateOrAll($query->latest(), $request);

        return ApiResponse::success($retailers, 'Trashed retailers fetched.');
    }

    /**
     * Restore retailer
     */
    public function restore($id)
    {
        $retailer = User::onlyTrashed()
            ->where('agency_id', $this->user->id)
            ->where('role', 'retailer')
            ->find($id);

        if (!$retailer) {
            return ApiResponse::error('Retailer not found in trash.');
        }

        $retailer->restore();

        return ApiResponse::success($retailer, 'Retailer restored successfully.');
    }

    /**
     * Force delete retailer
     */
    public function forceDelete($id)
    {
        $retailer = User::onlyTrashed()
            ->where('agency_id', $this->user->id)
            ->where('role', 'retailer')
            ->find($id);

        if (!$retailer) {
            return ApiResponse::error('Retailer not found in trash.');
        }

        $retailer->forceDelete();

        return ApiResponse::success([], 'Retailer permanently deleted.');
    }

    /**
     * Bulk Delete
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        User::where('agency_id', $this->user->id)
            ->where('role', 'retailer')
            ->whereIn('id', $request->ids)
            ->delete();

        return ApiResponse::success([], 'Selected retailers deleted.');
    }

    /**
     * Bulk Restore
     */

    public function bulkRestore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        User::onlyTrashed()
            ->where('agency_id', $this->user->id)
            ->where('role', 'retailer')
            ->whereIn('id', $request->ids)
            ->restore();

        return ApiResponse::success([], 'Selected retailers restored.');
    }

    /**
     * Generate a unique temporary email from the name.
     */
    private function generateTempEmail(string $name): string
    {
        $base = Str::slug($name, '.');
        $email = $base . '@temp';
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = $base . $counter . '@temp';
            $counter++;
        }

        return $email;
    }

    /**
     * Generate a secure password without confusing characters.
     */
    private function generatePassword(int $length = 8): string
    {
        $characters = '34679ABCDEFGHJKMNPQRTUVWXY!@#$';
        $password = '';
        $maxIndex = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $maxIndex)];
        }

        return $password;
    }
}
