<?php

return [
    'birth_certificate' => [
        'name' => 'Birth Certificate (PSA/NSO)',
        'description' => 'Extracts child\'s information, parents\' details, and registry data',
        'icon' => 'fas fa-baby',
        'color' => 'blue',
        'sections' => [
            'child_info' => [
                'title' => 'Child\'s Information',
                'icon' => 'fas fa-child',
                'color' => 'blue',
                'fields' => [
                    'name_first' => ['label' => 'First Name', 'type' => 'text', 'required' => true],
                    'name_middle' => ['label' => 'Middle Name', 'type' => 'text', 'required' => false],
                    'name_last' => ['label' => 'Last Name', 'type' => 'text', 'required' => true],
                    'sex' => ['label' => 'Sex', 'type' => 'radio', 'options' => ['Male', 'Female'], 'required' => true],
                    'birth_date_day' => ['label' => 'Birth Day', 'type' => 'text', 'maxlength' => 2, 'required' => true],
                    'birth_date_month' => ['label' => 'Birth Month', 'type' => 'text', 'required' => true],
                    'birth_date_year' => ['label' => 'Birth Year', 'type' => 'text', 'maxlength' => 4, 'required' => true],
                ]
            ],
            'birth_place' => [
                'title' => 'Place of Birth',
                'icon' => 'fas fa-map-marker-alt',
                'color' => 'orange',
                'fields' => [
                    'birth_place_institution' => ['label' => 'Hospital/Institution', 'type' => 'text'],
                    'birth_place_city' => ['label' => 'City/Municipality', 'type' => 'text', 'required' => true],
                    'birth_place_province' => ['label' => 'Province', 'type' => 'text', 'required' => true],
                ]
            ],
            'parents_info' => [
                'title' => 'Parents\' Information',
                'icon' => 'fas fa-users',
                'color' => 'green',
                'fields' => [
                    'mother_first_name' => ['label' => 'Mother\'s First Name', 'type' => 'text'],
                    'mother_middle_name' => ['label' => 'Mother\'s Middle Name', 'type' => 'text'],
                    'mother_last_name' => ['label' => 'Mother\'s Last Name', 'type' => 'text'],
                    'father_first_name' => ['label' => 'Father\'s First Name', 'type' => 'text'],
                    'father_middle_name' => ['label' => 'Father\'s Middle Name', 'type' => 'text'],
                    'father_last_name' => ['label' => 'Father\'s Last Name', 'type' => 'text'],
                ]
            ],
            'registry_info' => [
                'title' => 'Registry Information',
                'icon' => 'fas fa-id-card',
                'color' => 'indigo',
                'fields' => [
                    'registry_number' => ['label' => 'Registry Number', 'type' => 'text'],
                    'bren_number' => ['label' => 'BReN Number', 'type' => 'text'],
                    'citizenship' => ['label' => 'Citizenship', 'type' => 'text'],
                    'religion' => ['label' => 'Religion', 'type' => 'text'],
                ]
            ]
        ],
        'ocr_patterns' => [
            'name_patterns' => ['FIRST NAME', 'MIDDLE NAME', 'LAST NAME', 'PANGALAN'],
            'date_patterns' => ['DATE OF BIRTH', 'PETSA NG KAPANGANAKAN'],
            'place_patterns' => ['PLACE OF BIRTH', 'LUGAR NG KAPANGANAKAN'],
            'parent_patterns' => ['MOTHER', 'FATHER', 'INA', 'AMA']
        ]
    ],

    'death_certificate' => [
        'name' => 'Death Certificate (PSA/NSO)',
        'description' => 'Extracts deceased person\'s information, death details, and informant data',
        'icon' => 'fas fa-cross',
        'color' => 'red',
        'sections' => [
            'deceased_info' => [
                'title' => 'Deceased Person\'s Information',
                'icon' => 'fas fa-user-times',
                'color' => 'red',
                'fields' => [
                    'deceased_first_name' => ['label' => 'First Name', 'type' => 'text', 'required' => true],
                    'deceased_middle_name' => ['label' => 'Middle Name', 'type' => 'text'],
                    'deceased_last_name' => ['label' => 'Last Name', 'type' => 'text', 'required' => true],
                    'deceased_sex' => ['label' => 'Sex', 'type' => 'radio', 'options' => ['Male', 'Female']],
                    'deceased_age' => ['label' => 'Age at Death', 'type' => 'number', 'required' => true],
                    'deceased_civil_status' => ['label' => 'Civil Status', 'type' => 'text'],
                ]
            ],
            'death_details' => [
                'title' => 'Death Information',
                'icon' => 'fas fa-calendar-times',
                'color' => 'gray',
                'fields' => [
                    'death_date_day' => ['label' => 'Death Day', 'type' => 'text', 'maxlength' => 2, 'required' => true],
                    'death_date_month' => ['label' => 'Death Month', 'type' => 'text', 'required' => true],
                    'death_date_year' => ['label' => 'Death Year', 'type' => 'text', 'maxlength' => 4, 'required' => true],
                    'death_place_institution' => ['label' => 'Place of Death (Hospital/Home)', 'type' => 'text'],
                    'death_place_city' => ['label' => 'City/Municipality', 'type' => 'text', 'required' => true],
                    'death_place_province' => ['label' => 'Province', 'type' => 'text', 'required' => true],
                    'cause_of_death' => ['label' => 'Immediate Cause of Death', 'type' => 'textarea', 'required' => true],
                ]
            ],
            'informant_info' => [
                'title' => 'Informant Details',
                'icon' => 'fas fa-user-edit',
                'color' => 'purple',
                'fields' => [
                    'informant_name' => ['label' => 'Informant Name', 'type' => 'text'],
                    'informant_relationship' => ['label' => 'Relationship to Deceased', 'type' => 'text'],
                    'informant_address' => ['label' => 'Informant Address', 'type' => 'text'],
                ]
            ],
            'registry_info' => [
                'title' => 'Registry Information',
                'icon' => 'fas fa-file-medical',
                'color' => 'indigo',
                'fields' => [
                    'death_registry_number' => ['label' => 'Registry Number', 'type' => 'text'],
                    'death_certificate_number' => ['label' => 'Certificate Number', 'type' => 'text'],
                ]
            ]
        ],
        'ocr_patterns' => [
            'name_patterns' => ['NAME OF DECEASED', 'PANGALAN NG NAMATAY'],
            'date_patterns' => ['DATE OF DEATH', 'PETSA NG KAMATAYAN'],
            'place_patterns' => ['PLACE OF DEATH', 'LUGAR NG KAMATAYAN'],
            'cause_patterns' => ['CAUSE OF DEATH', 'DAHILAN NG KAMATAYAN']
        ]
    ],

    'marriage_certificate' => [
        'name' => 'Marriage Certificate (PSA/NSO)',
        'description' => 'Extracts bride and groom information, marriage details, and witnesses',
        'icon' => 'fas fa-rings-wedding',
        'color' => 'pink',
        'sections' => [
            'husband_info' => [
                'title' => 'Husband\'s Information',
                'icon' => 'fas fa-male',
                'color' => 'blue',
                'fields' => [
                    'husband_first_name' => ['label' => 'First Name', 'type' => 'text', 'required' => true],
                    'husband_middle_name' => ['label' => 'Middle Name', 'type' => 'text'],
                    'husband_last_name' => ['label' => 'Last Name', 'type' => 'text', 'required' => true],
                    'husband_age' => ['label' => 'Age at Marriage', 'type' => 'number'],
                    'husband_civil_status' => ['label' => 'Civil Status Before Marriage', 'type' => 'text'],
                    'husband_citizenship' => ['label' => 'Citizenship', 'type' => 'text'],
                ]
            ],
            'wife_info' => [
                'title' => 'Wife\'s Information',
                'icon' => 'fas fa-female',
                'color' => 'pink',
                'fields' => [
                    'wife_first_name' => ['label' => 'First Name', 'type' => 'text', 'required' => true],
                    'wife_middle_name' => ['label' => 'Middle Name', 'type' => 'text'],
                    'wife_maiden_name' => ['label' => 'Maiden Name', 'type' => 'text', 'required' => true],
                    'wife_age' => ['label' => 'Age at Marriage', 'type' => 'number'],
                    'wife_civil_status' => ['label' => 'Civil Status Before Marriage', 'type' => 'text'],
                    'wife_citizenship' => ['label' => 'Citizenship', 'type' => 'text'],
                ]
            ],
            'marriage_details' => [
                'title' => 'Marriage Information',
                'icon' => 'fas fa-heart',
                'color' => 'red',
                'fields' => [
                    'marriage_date_day' => ['label' => 'Marriage Day', 'type' => 'text', 'maxlength' => 2, 'required' => true],
                    'marriage_date_month' => ['label' => 'Marriage Month', 'type' => 'text', 'required' => true],
                    'marriage_date_year' => ['label' => 'Marriage Year', 'type' => 'text', 'maxlength' => 4, 'required' => true],
                    'marriage_place_institution' => ['label' => 'Place of Marriage (Church/City Hall)', 'type' => 'text'],
                    'marriage_place_city' => ['label' => 'City/Municipality', 'type' => 'text', 'required' => true],
                    'marriage_place_province' => ['label' => 'Province', 'type' => 'text', 'required' => true],
                    'officiant_name' => ['label' => 'Officiant Name', 'type' => 'text'],
                    'officiant_title' => ['label' => 'Officiant Title', 'type' => 'text'],
                ]
            ],
            'registry_info' => [
                'title' => 'Registry Information',
                'icon' => 'fas fa-certificate',
                'color' => 'indigo',
                'fields' => [
                    'marriage_registry_number' => ['label' => 'Registry Number', 'type' => 'text'],
                    'marriage_license_number' => ['label' => 'Marriage License Number', 'type' => 'text'],
                ]
            ]
        ],
        'ocr_patterns' => [
            'name_patterns' => ['HUSBAND', 'WIFE', 'GROOM', 'BRIDE', 'ASAWA'],
            'date_patterns' => ['DATE OF MARRIAGE', 'PETSA NG KASAL'],
            'place_patterns' => ['PLACE OF MARRIAGE', 'LUGAR NG KASAL'],
            'officiant_patterns' => ['OFFICIANT', 'CELEBRANT', 'PRIEST', 'JUDGE']
        ]
    ],

    'cenomar' => [
        'name' => 'CENOMAR (Certificate of No Marriage)',
        'description' => 'Extracts personal information and certification details',
        'icon' => 'fas fa-user-check',
        'color' => 'teal',
        'sections' => [
            'personal_info' => [
                'title' => 'Personal Information',
                'icon' => 'fas fa-id-card',
                'color' => 'teal',
                'fields' => [
                    'person_first_name' => ['label' => 'First Name', 'type' => 'text', 'required' => true],
                    'person_middle_name' => ['label' => 'Middle Name', 'type' => 'text'],
                    'person_last_name' => ['label' => 'Last Name', 'type' => 'text', 'required' => true],
                    'person_birth_date' => ['label' => 'Date of Birth', 'type' => 'date'],
                    'person_birth_place' => ['label' => 'Place of Birth', 'type' => 'text'],
                    'person_citizenship' => ['label' => 'Citizenship', 'type' => 'text'],
                ]
            ],
            'certification_info' => [
                'title' => 'Certification Details',
                'icon' => 'fas fa-stamp',
                'color' => 'green',
                'fields' => [
                    'certification_date' => ['label' => 'Date of Certification', 'type' => 'date'],
                    'issuing_office' => ['label' => 'Issuing Office', 'type' => 'text'],
                    'reference_number' => ['label' => 'Reference Number', 'type' => 'text'],
                ]
            ]
        ],
        'ocr_patterns' => [
            'name_patterns' => ['FULL NAME', 'BUONG PANGALAN'],
            'cert_patterns' => ['CERTIFICATE', 'CERTIFICATION', 'WALANG REKORD']
        ]
    ]
];