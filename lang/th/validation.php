<?php

declare(strict_types=1);

/**
 * Thai validation messages. Kept minimal — only rules that this app
 * actually uses. Missing keys fall back to Laravel's English defaults
 * automatically.
 *
 * Attribute aliases at the bottom let us say ":attribute is required"
 * with a human-readable label instead of "imei_serial is required".
 */
return [
    'required'  => 'กรุณากรอก :attribute',
    'string'    => ':attribute ต้องเป็นข้อความ',
    'integer'   => ':attribute ต้องเป็นตัวเลข',
    'numeric'   => ':attribute ต้องเป็นตัวเลข',
    'email'     => ':attribute ต้องเป็นอีเมลที่ถูกต้อง',
    'confirmed' => 'การยืนยัน :attribute ไม่ตรงกัน',
    'unique'    => ':attribute นี้มีอยู่ในระบบแล้ว',
    'exists'    => 'ไม่พบ :attribute ในระบบ',
    'in'        => ':attribute ที่เลือกไม่ถูกต้อง',

    'min' => [
        'string'  => ':attribute ต้องมีอย่างน้อย :min ตัวอักษร',
        'numeric' => ':attribute ต้องไม่น้อยกว่า :min',
        'array'   => ':attribute ต้องมีอย่างน้อย :min รายการ',
        'file'    => ':attribute ต้องมีขนาดอย่างน้อย :min KB',
    ],
    'max' => [
        'string'  => ':attribute ต้องมีความยาวไม่เกิน :max ตัวอักษร',
        'numeric' => ':attribute ต้องไม่เกิน :max',
        'array'   => ':attribute ต้องมีไม่เกิน :max รายการ',
        'file'    => ':attribute ต้องมีขนาดไม่เกิน :max KB',
    ],
    'between' => [
        'string'  => ':attribute ต้องมีความยาวระหว่าง :min ถึง :max ตัวอักษร',
        'numeric' => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
    ],
    'regex'          => ':attribute มีรูปแบบไม่ถูกต้อง',
    'date'           => ':attribute ต้องเป็นวันที่ที่ถูกต้อง',
    'before_or_equal'=> ':attribute ต้องเป็นวันที่ไม่หลัง :date',
    'mimes'          => ':attribute ต้องเป็นไฟล์ประเภท: :values',
    'file'           => ':attribute ต้องเป็นไฟล์',
    'current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
    'password' => [
        'letters' => ':attribute ต้องมีตัวอักษรอย่างน้อยหนึ่งตัว',
        'mixed'   => ':attribute ต้องมีตัวพิมพ์ใหญ่และตัวพิมพ์เล็กอย่างละหนึ่งตัว',
        'numbers' => ':attribute ต้องมีตัวเลขอย่างน้อยหนึ่งตัว',
        'symbols' => ':attribute ต้องมีอักขระพิเศษอย่างน้อยหนึ่งตัว',
        'uncompromised' => ':attribute นี้อยู่ในรายการรั่วไหลข้อมูล กรุณาเลือกใหม่',
    ],
    'throttle' => 'พยายามมากเกินไป กรุณาลองใหม่ในอีก :seconds วินาที',

    /*
    |--------------------------------------------------------------------------
    | Attribute Aliases
    |--------------------------------------------------------------------------
    | Displays friendly names instead of raw snake_case field names.
    */
    'attributes' => [
        'imei_serial'          => 'IMEI / Serial Number',
        'service_id'           => 'บริการ',
        'email'                => 'อีเมล',
        'password'             => 'รหัสผ่าน',
        'password_confirmation'=> 'ยืนยันรหัสผ่าน',
        'current_password'     => 'รหัสผ่านปัจจุบัน',
        'name'                 => 'ชื่อ',
        'amount'               => 'จำนวนเงิน',
        'transfer_reference'   => 'เลขที่อ้างอิง',
        'transfer_date'        => 'วันที่โอน',
        'bank_account_id'      => 'บัญชีธนาคาร',
        'slip'                 => 'สลิป',
        'rejection_reason'     => 'เหตุผลที่ไม่อนุมัติ',
        'bank_name'            => 'ชื่อธนาคาร',
        'account_number'       => 'เลขที่บัญชี',
        'account_name'         => 'ชื่อบัญชี',
    ],
];
