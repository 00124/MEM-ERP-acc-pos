<?php

namespace App\Http\Requests\Api\StockAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
        /**
         * Determine if the user is authorized to make this request.
         *
         * @return bool
         */

        public function authorize()
        {
                return true;
        }

        /**
         * Get the validation rules that apply to the request.
         *
         * @return array
         */
        public function rules()
        {
                $rules = [
                        'product_id'    => 'required',
                        'quantity'      => 'required|numeric',
                        'adjustment_type' => 'sometimes|in:add,subtract',
                        'warranty_type' => 'nullable|in:damage,expired,claimable,return_to_vendor,replacement',
                        'status'        => 'sometimes|in:pending,approved,completed',
                        'remarks'       => 'nullable|string',
                        'notes'         => 'nullable|string',
                ];

                return $rules;
        }
}
