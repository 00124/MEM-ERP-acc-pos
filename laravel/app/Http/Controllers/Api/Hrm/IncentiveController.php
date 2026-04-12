<?php

namespace App\Http\Controllers\Api\Hrm;

use App\Http\Controllers\ApiBaseController;
use App\Models\Hrm\Incentive;
use App\Models\StaffMember;
use Examyou\RestAPI\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncentiveController extends ApiBaseController
{
    protected $model = Incentive::class;

    protected function modifyIndex($query)
    {
        $request = request();

        // Filter by employee
        if ($request->has('user_id') && $request->user_id != '') {
            $userId = $this->getIdFromHash($request->user_id);
            $query = $query->where('hrm_incentives.user_id', $userId);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query = $query->whereDate('hrm_incentives.date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $query = $query->whereDate('hrm_incentives.date', '<=', $request->date_to);
        }

        // Filter by month/year
        if ($request->has('month') && $request->month != '') {
            $query = $query->whereMonth('hrm_incentives.date', $request->month);
        }
        if ($request->has('year') && $request->year != '') {
            $query = $query->whereYear('hrm_incentives.date', $request->year);
        }

        $query = $query->with(['user:id,name,profile_image', 'order:id,invoice_number,total,order_date,order_status']);

        return $query->orderBy('hrm_incentives.date', 'desc');
    }

    // Monthly summary: total incentives per employee for a given month/year
    public function summary(Request $request)
    {
        $company = company();
        $month = (int) ($request->month ?? Carbon::now()->month);
        $year  = (int) ($request->year  ?? Carbon::now()->year);

        $rows = DB::table('hrm_incentives as hi')
            ->join('users as u', 'u.id', '=', 'hi.user_id')
            ->where('hi.company_id', $company->id)
            ->whereMonth('hi.date', $month)
            ->whereYear('hi.date', $year)
            ->select(
                'u.id as user_id',
                'u.name',
                'u.profile_image',
                DB::raw('SUM(hi.amount) as total_incentive'),
                DB::raw('COUNT(hi.id) as total_sales'),
                DB::raw('MAX(hi.type) as incentive_type')
            )
            ->groupBy('u.id', 'u.name', 'u.profile_image')
            ->orderByDesc('total_incentive')
            ->get();

        return ApiResponse::make('Success', ['data' => $rows]);
    }
}
