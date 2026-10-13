<?php

namespace App\Http\Requests;

use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class SellerRegistrationRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [];
        
        $rules['name']          = 'required|string|max:255';
        $rules['l_name']        = 'sometimes|required|string|max:255';
        $rules['email']         = 'required|email|unique:users|max:255';
        $rules['phone']         = 'required|string|max:20';
        $rules['password' ]     = 'required|string|min:6|confirmed';
        $rules['shop_name' ]    = 'required|max:255';
        $rules['address']       = 'required';
        $rules['business_category'] = ['required', Rule::in(['product', 'food', 'service'])];
        $rules['student_status'] = ['required', Rule::in(['student', 'non_student'])];
        $rules['school_id'] = 'nullable|required_if:student_status,student|exists:schools,id';
        $rules['school_id_front'] = 'nullable|required_if:student_status,student|file|mimes:jpg,jpeg,png,pdf|max:4096';
        $rules['school_id_back'] = 'nullable|required_if:student_status,student|file|mimes:jpg,jpeg,png,pdf|max:4096';
        $rules['g-recaptcha-response'] = [
                Rule::when(get_setting('google_recaptcha') == 1 && 
                get_setting('recaptcha_seller_register') == 1 , 
                ['required', new Recaptcha()], 
                ['sometimes'])
            ];
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required'         => translate('Name is required'),
            'name.string'           => translate('Name should be string type'),
            'l_name.required'       => translate('Last name is required'),
            'name.max'              => translate('Max 255 characters'),
            'email.required'        => translate('Email is required'),
            'email.email'           => translate('Please type a valid email'),
            'email.unique'          => translate('Email should be unique'),
            'email.max'             => translate('Max 255 characters'),
            'password.required'     => translate('Password is required'),
            'password.string'       => translate('Password should be string type'),
            'password.min'          => translate('Min 6 characters'),
            'password.confirmed'    => translate('Confirm password do not matched'),
            'shop_name.required'    => translate('Shop name is required'),
            'shop_name.max'         => translate('Max 255 characters'),
            'address.required'      => translate('Address is required'),
            'business_category.required' => translate('Business category is required'),
            'business_category.in'  => translate('Business category is invalid'),
            'student_status.required' => translate('Student status is required'),
            'school_id.required_if'    => translate('School is required for student sellers'),
            'school_id.exists'         => translate('Selected school is invalid'),
            'school_id_front.required_if' => translate('School ID front is required for student sellers'),
            'school_id_back.required_if' => translate('School ID back is required for student sellers'),
        ];
    }

    public function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => $validator->errors()->all(),
                'result' => false
            ], 422));
        } else {
            throw (new ValidationException($validator))
                    ->errorBag($this->errorBag)
                    ->redirectTo($this->getRedirectUrl());
        }
    }
}
