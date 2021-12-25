<?php

function get_glyphic_config()
{
    return [
        'tables_aka' => [
            'main_tennants'=>'m_glyf_tnt',
            'main_users' => 'm_glyf_user',
            'sub_profile'=>'s_glyf_profile',
            'sub_user_tree'=>'s_glyf_user_tree',
            'sub_user_contact'=>'s_glyf_user_cnt',
            'sub_user_address'=>'s_glyf_user_adrs',
            'sub_user_actions_tracker'=>'s_glyf_user_actrk',
            'main_verf_keys'=>'m_glyf_verf_keys'
        ],
        'default_status'=>[
            'tennant' => 'NEW'
        ],
        'instance_domain'=>'localhost',
        'instance_id'=>'',
        'current_pod_id' => 1
    ];
}
