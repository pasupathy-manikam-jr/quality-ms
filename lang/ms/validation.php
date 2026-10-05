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

    'accepted' => 'Medan :attribute mesti diterima.',
    'accepted_if' => 'Medan :attribute mesti diterima apabila :other ialah :value.',
    'active_url' => 'Medan :attribute mesti URL yang sah.',
    'after' => 'Medan :attribute mesti tarikh selepas :date.',
    'after_or_equal' => 'Medan :attribute mesti tarikh selepas atau sama dengan :date.',
    'alpha' => 'Medan :attribute hanya boleh mengandungi huruf.',
    'alpha_dash' => 'Medan :attribute hanya boleh mengandungi huruf, nombor, sengkang dan garis bawah.',
    'alpha_num' => 'Medan :attribute hanya boleh mengandungi huruf dan nombor.',
    'any_of' => 'Medan :attribute tidak sah.',
    'array' => 'Medan :attribute mesti tatasusunan.',
    'array_keys' => 'Medan :attribute hanya boleh mengandungi kunci berikut: :values.',
    'ascii' => 'Medan :attribute hanya boleh mengandungi aksara alfanumerik dan simbol bait tunggal.',
    'base64' => 'Medan :attribute mesti rentetan Base64 yang sah.',
    'before' => 'Medan :attribute mesti tarikh sebelum :date.',
    'before_or_equal' => 'Medan :attribute mesti tarikh sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Medan :attribute mesti mempunyai antara :min hingga :max item.',
        'file' => 'Medan :attribute mesti antara :min hingga :max kilobait.',
        'numeric' => 'Medan :attribute mesti antara :min hingga :max.',
        'string' => 'Medan :attribute mesti antara :min hingga :max aksara.',
    ],
    'boolean' => 'Medan :attribute mesti benar atau palsu.',
    'can' => 'Medan :attribute mengandungi nilai yang tidak dibenarkan.',
    'confirmed' => 'Pengesahan medan :attribute tidak sepadan.',
    'contains' => 'Medan :attribute tidak mempunyai nilai yang diperlukan.',
    'current_password' => 'Kata laluan tidak betul.',
    'date' => 'Medan :attribute mesti tarikh yang sah.',
    'date_equals' => 'Medan :attribute mesti tarikh yang sama dengan :date.',
    'date_format' => 'Medan :attribute mesti sepadan dengan format :format.',
    'decimal' => 'Medan :attribute mesti mempunyai :decimal tempat perpuluhan.',
    'declined' => 'Medan :attribute mesti ditolak.',
    'declined_if' => 'Medan :attribute mesti ditolak apabila :other ialah :value.',
    'different' => 'Medan :attribute dan :other mesti berbeza.',
    'digits' => 'Medan :attribute mesti :digits digit.',
    'digits_between' => 'Medan :attribute mesti antara :min hingga :max digit.',
    'dimensions' => 'Medan :attribute mempunyai dimensi imej yang tidak sah.',
    'distinct' => 'Medan :attribute mempunyai nilai pendua.',
    'doesnt_contain' => 'Medan :attribute tidak boleh mengandungi mana-mana yang berikut: :values.',
    'doesnt_end_with' => 'Medan :attribute tidak boleh berakhir dengan salah satu yang berikut: :values.',
    'doesnt_start_with' => 'Medan :attribute tidak boleh bermula dengan salah satu yang berikut: :values.',
    'email' => 'Medan :attribute mesti alamat e-mel yang sah.',
    'encoding' => 'Medan :attribute mesti dikodkan dalam :encoding.',
    'ends_with' => 'Medan :attribute mesti berakhir dengan salah satu yang berikut: :values.',
    'enum' => ':attribute yang dipilih tidak sah.',
    'exists' => ':attribute yang dipilih tidak sah.',
    'extensions' => 'Medan :attribute mesti mempunyai salah satu sambungan berikut: :values.',
    'file' => 'Medan :attribute mesti fail.',
    'filled' => 'Medan :attribute mesti mempunyai nilai.',
    'gt' => [
        'array' => 'Medan :attribute mesti mempunyai lebih daripada :value item.',
        'file' => 'Medan :attribute mesti lebih besar daripada :value kilobait.',
        'numeric' => 'Medan :attribute mesti lebih besar daripada :value.',
        'string' => 'Medan :attribute mesti lebih daripada :value aksara.',
    ],
    'gte' => [
        'array' => 'Medan :attribute mesti mempunyai :value item atau lebih.',
        'file' => 'Medan :attribute mesti lebih besar daripada atau sama dengan :value kilobait.',
        'numeric' => 'Medan :attribute mesti lebih besar daripada atau sama dengan :value.',
        'string' => 'Medan :attribute mesti sekurang-kurangnya :value aksara.',
    ],
    'hex_color' => 'Medan :attribute mesti warna heksadesimal yang sah.',
    'image' => 'Medan :attribute mesti imej.',
    'in' => ':attribute yang dipilih tidak sah.',
    'in_array' => 'Medan :attribute mesti wujud dalam :other.',
    'in_array_keys' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu daripada kunci berikut: :values.',
    'integer' => 'Medan :attribute mesti integer.',
    'ip' => 'Medan :attribute mesti alamat IP yang sah.',
    'ipv4' => 'Medan :attribute mesti alamat IPv4 yang sah.',
    'ipv6' => 'Medan :attribute mesti alamat IPv6 yang sah.',
    'json' => 'Medan :attribute mesti rentetan JSON yang sah.',
    'list' => 'Medan :attribute mesti senarai.',
    'lowercase' => 'Medan :attribute mesti huruf kecil.',
    'lt' => [
        'array' => 'Medan :attribute mesti mempunyai kurang daripada :value item.',
        'file' => 'Medan :attribute mesti kurang daripada :value kilobait.',
        'numeric' => 'Medan :attribute mesti kurang daripada :value.',
        'string' => 'Medan :attribute mesti kurang daripada :value aksara.',
    ],
    'lte' => [
        'array' => 'Medan :attribute tidak boleh mempunyai lebih daripada :value item.',
        'file' => 'Medan :attribute mesti kurang daripada atau sama dengan :value kilobait.',
        'numeric' => 'Medan :attribute mesti kurang daripada atau sama dengan :value.',
        'string' => 'Medan :attribute tidak boleh melebihi :value aksara.',
    ],
    'mac_address' => 'Medan :attribute mesti alamat MAC yang sah.',
    'max' => [
        'array' => 'Medan :attribute tidak boleh mempunyai lebih daripada :max item.',
        'file' => 'Medan :attribute tidak boleh melebihi :max kilobait.',
        'numeric' => 'Medan :attribute tidak boleh melebihi :max.',
        'string' => 'Medan :attribute tidak boleh melebihi :max aksara.',
    ],
    'max_digits' => 'Medan :attribute tidak boleh mempunyai lebih daripada :max digit.',
    'mimes' => 'Medan :attribute mesti fail jenis: :values.',
    'mimetypes' => 'Medan :attribute mesti fail jenis: :values.',
    'min' => [
        'array' => 'Medan :attribute mesti mempunyai sekurang-kurangnya :min item.',
        'file' => 'Medan :attribute mesti sekurang-kurangnya :min kilobait.',
        'numeric' => 'Medan :attribute mesti sekurang-kurangnya :min.',
        'string' => 'Medan :attribute mesti sekurang-kurangnya :min aksara.',
    ],
    'min_digits' => 'Medan :attribute mesti mempunyai sekurang-kurangnya :min digit.',
    'missing' => 'Medan :attribute mesti tiada.',
    'missing_if' => 'Medan :attribute mesti tiada apabila :other ialah :value.',
    'missing_unless' => 'Medan :attribute mesti tiada kecuali :other ialah :value.',
    'missing_with' => 'Medan :attribute mesti tiada apabila :values wujud.',
    'missing_with_all' => 'Medan :attribute mesti tiada apabila :values wujud.',
    'multiple_of' => 'Medan :attribute mesti gandaan :value.',
    'not_in' => ':attribute yang dipilih tidak sah.',
    'not_regex' => 'Format medan :attribute tidak sah.',
    'numeric' => 'Medan :attribute mesti nombor.',
    'password' => [
        'letters' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu huruf.',
        'mixed' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu nombor.',
        'symbols' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu simbol.',
        'uncompromised' => ':attribute yang diberikan telah muncul dalam kebocoran data. Sila pilih :attribute yang lain.',
    ],
    'present' => 'Medan :attribute mesti wujud.',
    'present_if' => 'Medan :attribute mesti wujud apabila :other ialah :value.',
    'present_unless' => 'Medan :attribute mesti wujud kecuali :other ialah :value.',
    'present_with' => 'Medan :attribute mesti wujud apabila :values wujud.',
    'present_with_all' => 'Medan :attribute mesti wujud apabila :values wujud.',
    'prohibited' => 'Medan :attribute adalah dilarang.',
    'prohibited_if' => 'Medan :attribute dilarang apabila :other ialah :value.',
    'prohibited_if_accepted' => 'Medan :attribute dilarang apabila :other diterima.',
    'prohibited_if_declined' => 'Medan :attribute dilarang apabila :other ditolak.',
    'prohibited_unless' => 'Medan :attribute dilarang kecuali :other berada dalam :values.',
    'prohibits' => 'Medan :attribute melarang :other daripada wujud.',
    'regex' => 'Format medan :attribute tidak sah.',
    'required' => 'Medan :attribute diperlukan.',
    'required_array_keys' => 'Medan :attribute mesti mengandungi entri untuk: :values.',
    'required_if' => 'Medan :attribute diperlukan apabila :other ialah :value.',
    'required_if_accepted' => 'Medan :attribute diperlukan apabila :other diterima.',
    'required_if_declined' => 'Medan :attribute diperlukan apabila :other ditolak.',
    'required_unless' => 'Medan :attribute diperlukan kecuali :other berada dalam :values.',
    'required_with' => 'Medan :attribute diperlukan apabila :values wujud.',
    'required_with_all' => 'Medan :attribute diperlukan apabila :values wujud.',
    'required_without' => 'Medan :attribute diperlukan apabila :values tiada.',
    'required_without_all' => 'Medan :attribute diperlukan apabila tiada satu pun daripada :values wujud.',
    'same' => 'Medan :attribute mesti sepadan dengan :other.',
    'size' => [
        'array' => 'Medan :attribute mesti mengandungi :size item.',
        'file' => 'Medan :attribute mesti :size kilobait.',
        'numeric' => 'Medan :attribute mesti :size.',
        'string' => 'Medan :attribute mesti :size aksara.',
    ],
    'starts_with' => 'Medan :attribute mesti bermula dengan salah satu yang berikut: :values.',
    'string' => 'Medan :attribute mesti rentetan.',
    'timezone' => 'Medan :attribute mesti zon waktu yang sah.',
    'unique' => ':attribute telah pun digunakan.',
    'uploaded' => ':attribute gagal dimuat naik.',
    'uppercase' => 'Medan :attribute mesti huruf besar.',
    'url' => 'Medan :attribute mesti URL yang sah.',
    'ulid' => 'Medan :attribute mesti ULID yang sah.',
    'uuid' => 'Medan :attribute mesti UUID yang sah.',

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
