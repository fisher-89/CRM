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

    'accepted' => ':attribute 必须为“是”。',
    'active_url' => ':attribute 必须是一个可访问的地址。',
    'after' => ':attribute 必须晚于 :date。',
    'after_or_equal' => ':attribute 必须晚于或等于 :date。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字和点。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'array' => ':attribute 必须是数组。',
    'before' => ':attribute 必须早于 :date。',
    'before_or_equal' => ':attribute 必须早于或等于 :date。',
    'between' => [
        'numeric' => ':attribute 必须介于 :min 和 :max 之间。',
        'file' => ':attribute 文件大小必须介于 :minK 和 :maxK 之间。',
        'string' => ':attribute 必须包含 :min 和 :max 个字符。',
        'array' => ':attribute 必须包含 :min 到 :max 个元素。',
    ],
    'boolean' => ':attribute 必须为“是”或“否”。',
    'confirmed' => ':attribute 两次输入不一致。',
    'date' => ':attribute 必须是日期格式。',
    'date_format' => ':attribute 必须是格式为 :format 的日期。',
    'different' => ':attribute 和 :other 不能相同。',
    'digits' => ':attribute 必须是 :digits 位。',
    'digits_between' => ':attribute 必须介于 :min 和 :max 位之间。',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => ':attribute 不可出现重复值。',
    'email' => ':attribute 必须是电子邮件格式。',
    'exists' => ':attribute 记录不存在。',
    'file' => ':attribute 必须是文件。',
    'filled' => ':attribute 不能为空。',
    'image' => ':attribute 必须是图片。',
    'in' => ':attribute 不在可选范围中。',
    'in_array' => ':attribute 必须包含在 :other 中。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是一个有效的IP地址。',
    'ipv4' => ':attribute 必须是一个有效的IPv4地址。',
    'ipv6' => ':attribute 必须是一个有效的IPv6地址。',
    'json' => ':attribute 必须是json字符串。',
    'max' => [
        'numeric' => ':attribute 不能大于 :max 。',
        'file' => ':attribute 文件大小不能大于 :maxK 。',
        'string' => ':attribute 长度不能大于 :max 。',
        'array' => ':attribute 不能包含超过 :max 个元素。',
    ],
    'mimes' => ':attribute 必须是 :values 格式的文件。',
    'mimetypes' => ':attribute 必须是 :values 格式的文件。',
    'min' => [
        'numeric' => ':attribute 不能小于 :min 。',
        'file' => ':attribute 文件大小不能小于 :minK 。',
        'string' => ':attribute 长度不能小于 :min 。',
        'array' => ':attribute 必须包含 :min 个以上元素。',
    ],
    'not_in' => ':attribute 禁止使用该值。',
    'numeric' => ':attribute 必须是数字。',
    'present' => '参数必须包含 :attribute。',
    'regex' => ':attribute 格式不正确。',
    'required' => ':attribute 不能为空。',
    'required_if' => '当 :other 等于 :value 时，:attribute 不能为空。',
    'required_unless' => '除非 :other 在 :values 中，:attribute 不能为空。',
    'required_with' => ':values 存在时， :attribute 不能为空。',
    'required_with_all' => ':values 都存在时， :attribute 不能为空。',
    'required_without' => ':values 不存在时， :attribute 不能为空。',
    'required_without_all' => ':values 都不存在时， :attribute 不能为空。',
    'same' => ':attribute 和 :other 必须相同。',
    'size' => [
        'numeric' => ':attribute 必须等于 :size 。',
        'file' => ':attribute 文件大小必须是 :sizeK。',
        'string' => ':attribute 长度必须是 :size 。',
        'array' => ':attribute 必须包含 :size 个元素。',
    ],
    'string' => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是时区标识。',
    'unique' => ':attribute 已经存在。',
    'uploaded' => ':attribute 上传失败。',
    'url' => 'The :attribute format is invalid.',

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
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        /*报销单*/
        'description' => '描述',
        'remark' => '备注',
        'reim_department_id' => '资金归属',
        'payee_id' => '收款人ID',
        'payee_name' => '收款人',
        'approver_staff_sn' => '审批人编号',
        'approver_name' => '审批人',
        'expenses' => '消费明细',
        'expenses.*.date' => '消费日期',
        'expenses.*.type_id' => '消费类型',
        'expenses.*.send_cost' => '消费金额',
        'expenses.*.description' => '消费明细描述',
        'expenses.*.bill' => '发票',
        'expenses.*.bill.*' => '发票',
        /*驳回*/
        'reject_remarks' => '驳回原因',
        /*收款人*/
        'bank_account_name' => '开户名',
        'phone' => '收款人手机',
        'bank_account' => '银行卡号',
        'bank_other' => '银行类型',
        'bank_dot' => '开户网点',
        'province_of_account' => '开户行所在省',
        'city_of_account' => '开户行所在市',
    ],

];
