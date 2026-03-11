<?php

namespace App\Http\Requests\Api\Grn;

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
            'user_id'       => 'required',
            'warehouse_id'  => 'required',
            'order_date'    => 'required',
            'order_status'  => 'required',
            'product_items' => 'required|array|min:1',
        ];
    }

    public function messages()
    {
        return [
            'user_id.required'       => 'Supplier is required.',
            'warehouse_id.required'  => 'Warehouse is required.',
            'order_date.required'    => 'GRN date is required.',
            'product_items.required' => 'At least one item is required.',
            'product_items.min'      => 'At least one item is required.',
        ];
    }
}
