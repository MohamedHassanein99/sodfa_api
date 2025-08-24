<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'product_advantage' => 'nullable|string',
            'price' => 'required|numeric',
            'is_featured' => 'boolean',

            'colors' => 'required|array|min:1',
            'colors.*.color_id' => 'required|exists:colors,id',
            'colors.*.image' => 'required|image|mimes:jpg,jpeg,png|max:10240',

        ];
    }
}
