<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class StoreRmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string'],
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'reason' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'conditions' => ['accepted'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ];
    }

    public function attributes(): array
    {
        return [
            'order_number' => 'commande',
            'order_item_id' => 'produit',
            'reason' => 'motif',
            'description' => 'message',
            'conditions' => 'conditions',
            'attachments.*' => 'pièce jointe',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Merci d’indiquer un message.',
            'conditions.accepted' => 'Merci d’accepter les conditions (film de protection intact et produit non monté).',
            'attachments.*.uploaded' => 'Le fichier n’a pas pu être téléversé (taille maximale serveur ou upload interrompu). Essayez un fichier plus léger.',
            'attachments.*.max' => 'Le fichier est trop volumineux (max : 5 Mo).',
            'attachments.*.mimes' => 'Format de fichier non supporté (JPG, PNG, WEBP ou PDF).',
        ];
    }
}
