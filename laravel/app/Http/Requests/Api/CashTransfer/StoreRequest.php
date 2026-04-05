<?php

namespace App\Http\Requests\Api\CashTransfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'from_warehouse_id' => 'required',
            'to_warehouse_id'   => 'required',
            'amount'            => 'required|numeric|min:1',
            'transfer_date'     => 'required|date',
            'transfer_type'     => 'required|in:ho_to_branch,branch_to_ho,branch_to_branch',
        ];
    }
}
