<?php

namespace App\Http\Controllers\Api\Web;

use App\Helpers\ApiResponse;
use App\Models\AgencyDetail;
use Illuminate\Http\Request;
use App\Helpers\ImageStorageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AgencyDetailController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:255',
            'state'        => 'nullable|string|max:255',
            'country'      => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:20',
        ]);
        return $request->all();
        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
        }

        $agency = currentAccount();

        if ($request->hasFile('company_logo')) {
            $logoPath = ImageStorageHelper::store($request->file('company_logo'), 'company-logo');
        }

        $detail = AgencyDetail::create([
            'agency_id'     => $agency->id,
            'company_name'  => $request->company_name,
            'company_logo'  => $logoPath,
            'address'       => $request->address,
            'city'          => $request->city,
            'state'         => $request->state,
            'country'       => $request->country,
            'phone'         => $request->phone,
        ]);

        return ApiResponse::success($detail, 'Agency details stored successfully.');
    }

    // public function update(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'company_name' => 'required|string|max:255',
    //         'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
    //         'address'      => 'nullable|string',
    //         'city'         => 'nullable|string|max:255',
    //         'state'        => 'nullable|string|max:255',
    //         'country'      => 'nullable|string|max:255',
    //         'phone'        => 'nullable|string|max:20',
    //     ]);

    //     if ($validator->fails()) {
    //         return ApiResponse::validationError($validator->errors(), 'Please correct the highlighted errors.');
    //     }

    //     $agency = currentAccount();

    //     $detail = AgencyDetail::where('agency_id', $agency->id)->first();

    //     if ($request->hasFile('company_logo')) {
    //         $detail->company_logo = ImageStorageHelper::update($request->file('company_logo'), $detail->company_logo, 'company-logo');
    //     }

    //     $detail->update([
    //         'company_name' => $request->company_name,
    //         'address'      => $request->address,
    //         'city'         => $request->city,
    //         'state'        => $request->state,
    //         'country'      => $request->country,
    //         'phone'        => $request->phone,
    //     ]);

    //     return ApiResponse::success($detail, 'Agency details updated successfully.');
    // }
}
