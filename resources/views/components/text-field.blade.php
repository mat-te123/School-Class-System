@props([
    'label' => null,
    'name' => '',
    'type' => '',
    'placeholder' => '',
    'value' => '',
    'require' => false

])

<div>
    <input 
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        
    >
</div>