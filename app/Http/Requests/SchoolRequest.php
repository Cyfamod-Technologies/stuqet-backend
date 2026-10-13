<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchoolRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('school');
        return [
            'name' => 'required|max:255',
            'slug' => 'nullable|unique:schools,slug,' . $id,
            'logo' => 'nullable|integer',
            'meta_title' => 'nullable|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => translate('The school name is required.'),
            'name.max' => translate('The school name may not be greater than 255 characters.'),
            'slug.unique' => translate('The slug has already been taken.'),
            'logo.integer' => translate('The logo must be an image'),
            'meta_title.max' => translate('The meta title may not be greater than 255 characters.'),
            'meta_description.string' => translate('The meta description must be a string.'),
        ];
    }
}
