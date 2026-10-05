<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute 字段必须被接受。',
    'accepted_if' => '当 :other 为 :value 时，:attribute 字段必须被接受。',
    'active_url' => ':attribute 字段必须是有效的 URL。',
    'after' => ':attribute 字段必须是 :date 之后的日期。',
    'after_or_equal' => ':attribute 字段必须是 :date 或之后的日期。',
    'alpha' => ':attribute 字段只能包含字母。',
    'alpha_dash' => ':attribute 字段只能包含字母、数字、短横线和下划线。',
    'alpha_num' => ':attribute 字段只能包含字母和数字。',
    'any_of' => ':attribute 字段无效。',
    'array' => ':attribute 字段必须是数组。',
    'array_keys' => ':attribute 字段只能包含以下键：:values。',
    'ascii' => ':attribute 字段只能包含单字节字母数字字符和符号。',
    'base64' => ':attribute 字段必须是有效的 Base64 字符串。',
    'before' => ':attribute 字段必须是 :date 之前的日期。',
    'before_or_equal' => ':attribute 字段必须是 :date 或之前的日期。',
    'between' => [
        'array' => ':attribute 字段必须包含 :min 到 :max 个项目。',
        'file' => ':attribute 字段必须介于 :min 到 :max KB 之间。',
        'numeric' => ':attribute 字段必须介于 :min 到 :max 之间。',
        'string' => ':attribute 字段必须介于 :min 到 :max 个字符之间。',
    ],
    'boolean' => ':attribute 字段必须为 true 或 false。',
    'can' => ':attribute 字段包含未经授权的值。',
    'confirmed' => ':attribute 字段的确认不匹配。',
    'contains' => ':attribute 字段缺少必需的值。',
    'current_password' => '密码不正确。',
    'date' => ':attribute 字段必须是有效的日期。',
    'date_equals' => ':attribute 字段必须是等于 :date 的日期。',
    'date_format' => ':attribute 字段必须符合格式 :format。',
    'decimal' => ':attribute 字段必须有 :decimal 位小数。',
    'declined' => ':attribute 字段必须被拒绝。',
    'declined_if' => '当 :other 为 :value 时，:attribute 字段必须被拒绝。',
    'different' => ':attribute 字段和 :other 必须不同。',
    'digits' => ':attribute 字段必须是 :digits 位数字。',
    'digits_between' => ':attribute 字段必须介于 :min 到 :max 位数字之间。',
    'dimensions' => ':attribute 字段的图片尺寸无效。',
    'distinct' => ':attribute 字段存在重复值。',
    'doesnt_contain' => ':attribute 字段不能包含以下任何一项：:values。',
    'doesnt_end_with' => ':attribute 字段不能以下列之一结尾：:values。',
    'doesnt_start_with' => ':attribute 字段不能以下列之一开头：:values。',
    'email' => ':attribute 字段必须是有效的电子邮件地址。',
    'encoding' => ':attribute 字段必须使用 :encoding 编码。',
    'ends_with' => ':attribute 字段必须以下列之一结尾：:values。',
    'enum' => '所选的 :attribute 无效。',
    'exists' => '所选的 :attribute 无效。',
    'extensions' => ':attribute 字段必须具有以下扩展名之一：:values。',
    'file' => ':attribute 字段必须是文件。',
    'filled' => ':attribute 字段必须有值。',
    'gt' => [
        'array' => ':attribute 字段必须多于 :value 个项目。',
        'file' => ':attribute 字段必须大于 :value KB。',
        'numeric' => ':attribute 字段必须大于 :value。',
        'string' => ':attribute 字段必须多于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 字段必须至少有 :value 个项目。',
        'file' => ':attribute 字段必须大于或等于 :value KB。',
        'numeric' => ':attribute 字段必须大于或等于 :value。',
        'string' => ':attribute 字段必须至少有 :value 个字符。',
    ],
    'hex_color' => ':attribute 字段必须是有效的十六进制颜色。',
    'image' => ':attribute 字段必须是图片。',
    'in' => '所选的 :attribute 无效。',
    'in_array' => ':attribute 字段必须存在于 :other 中。',
    'in_array_keys' => ':attribute 字段必须至少包含以下键之一：:values。',
    'integer' => ':attribute 字段必须是整数。',
    'ip' => ':attribute 字段必须是有效的 IP 地址。',
    'ipv4' => ':attribute 字段必须是有效的 IPv4 地址。',
    'ipv6' => ':attribute 字段必须是有效的 IPv6 地址。',
    'json' => ':attribute 字段必须是有效的 JSON 字符串。',
    'list' => ':attribute 字段必须是列表。',
    'lowercase' => ':attribute 字段必须为小写。',
    'lt' => [
        'array' => ':attribute 字段必须少于 :value 个项目。',
        'file' => ':attribute 字段必须小于 :value KB。',
        'numeric' => ':attribute 字段必须小于 :value。',
        'string' => ':attribute 字段必须少于 :value 个字符。',
    ],
    'lte' => [
        'array' => ':attribute 字段不能多于 :value 个项目。',
        'file' => ':attribute 字段必须小于或等于 :value KB。',
        'numeric' => ':attribute 字段必须小于或等于 :value。',
        'string' => ':attribute 字段不能多于 :value 个字符。',
    ],
    'mac_address' => ':attribute 字段必须是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 字段不能多于 :max 个项目。',
        'file' => ':attribute 字段不能大于 :max KB。',
        'numeric' => ':attribute 字段不能大于 :max。',
        'string' => ':attribute 字段不能多于 :max 个字符。',
    ],
    'max_digits' => ':attribute 字段不能多于 :max 位数字。',
    'mimes' => ':attribute 字段必须是以下类型的文件：:values。',
    'mimetypes' => ':attribute 字段必须是以下类型的文件：:values。',
    'min' => [
        'array' => ':attribute 字段必须至少有 :min 个项目。',
        'file' => ':attribute 字段必须至少为 :min KB。',
        'numeric' => ':attribute 字段必须至少为 :min。',
        'string' => ':attribute 字段必须至少有 :min 个字符。',
    ],
    'min_digits' => ':attribute 字段必须至少有 :min 位数字。',
    'missing' => ':attribute 字段必须不存在。',
    'missing_if' => '当 :other 为 :value 时，:attribute 字段必须不存在。',
    'missing_unless' => '除非 :other 为 :value，否则 :attribute 字段必须不存在。',
    'missing_with' => '当 :values 存在时，:attribute 字段必须不存在。',
    'missing_with_all' => '当 :values 都存在时，:attribute 字段必须不存在。',
    'multiple_of' => ':attribute 字段必须是 :value 的倍数。',
    'not_in' => '所选的 :attribute 无效。',
    'not_regex' => ':attribute 字段格式无效。',
    'numeric' => ':attribute 字段必须是数字。',
    'password' => [
        'letters' => ':attribute 字段必须至少包含一个字母。',
        'mixed' => ':attribute 字段必须至少包含一个大写字母和一个小写字母。',
        'numbers' => ':attribute 字段必须至少包含一个数字。',
        'symbols' => ':attribute 字段必须至少包含一个符号。',
        'uncompromised' => '提供的 :attribute 已出现在数据泄露中，请选择其他 :attribute。',
    ],
    'present' => ':attribute 字段必须存在。',
    'present_if' => '当 :other 为 :value 时，:attribute 字段必须存在。',
    'present_unless' => '除非 :other 为 :value，否则 :attribute 字段必须存在。',
    'present_with' => '当 :values 存在时，:attribute 字段必须存在。',
    'present_with_all' => '当 :values 都存在时，:attribute 字段必须存在。',
    'prohibited' => ':attribute 字段被禁止。',
    'prohibited_if' => '当 :other 为 :value 时，:attribute 字段被禁止。',
    'prohibited_if_accepted' => '当 :other 被接受时，:attribute 字段被禁止。',
    'prohibited_if_declined' => '当 :other 被拒绝时，:attribute 字段被禁止。',
    'prohibited_unless' => '除非 :other 在 :values 中，否则 :attribute 字段被禁止。',
    'prohibits' => ':attribute 字段禁止 :other 存在。',
    'regex' => ':attribute 字段格式无效。',
    'required' => ':attribute 字段为必填项。',
    'required_array_keys' => ':attribute 字段必须包含以下条目：:values。',
    'required_if' => '当 :other 为 :value 时，:attribute 字段为必填项。',
    'required_if_accepted' => '当 :other 被接受时，:attribute 字段为必填项。',
    'required_if_declined' => '当 :other 被拒绝时，:attribute 字段为必填项。',
    'required_unless' => '除非 :other 在 :values 中，否则 :attribute 字段为必填项。',
    'required_with' => '当 :values 存在时，:attribute 字段为必填项。',
    'required_with_all' => '当 :values 都存在时，:attribute 字段为必填项。',
    'required_without' => '当 :values 不存在时，:attribute 字段为必填项。',
    'required_without_all' => '当 :values 都不存在时，:attribute 字段为必填项。',
    'same' => ':attribute 字段必须与 :other 一致。',
    'size' => [
        'array' => ':attribute 字段必须包含 :size 个项目。',
        'file' => ':attribute 字段必须为 :size KB。',
        'numeric' => ':attribute 字段必须为 :size。',
        'string' => ':attribute 字段必须为 :size 个字符。',
    ],
    'starts_with' => ':attribute 字段必须以下列之一开头：:values。',
    'string' => ':attribute 字段必须是字符串。',
    'timezone' => ':attribute 字段必须是有效的时区。',
    'unique' => ':attribute 已被占用。',
    'uploaded' => ':attribute 上传失败。',
    'uppercase' => ':attribute 字段必须为大写。',
    'url' => ':attribute 字段必须是有效的 URL。',
    'ulid' => ':attribute 字段必须是有效的 ULID。',
    'uuid' => ':attribute 字段必须是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
