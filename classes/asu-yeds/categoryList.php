<?php

return array(

    //-------------------
    // Корневая категория
    array(
        'id'         => 1,
        'parent_id'  => 0,
        'id_pict'    => 1,
        'level_this' => 0,
        'markfordel' => false,
        'title'      => 'Оперативная обстановка',
        'name_max'   => 'Оперативная обстановка',
        'name_min'   => 'Оперативная обстановка',
        'note'       => '',
    ),

    //-------------------
    // Основные категории
    array(
        'id'         => 2,
        'parent_id'  => 1,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Санитарно-эпидемиологичеcкая обстановка',
        'name_max'   => 'Санитарно-эпидемиологичеcкая обстановка',
        'name_min'   => 'Санитарно-эпидемиологичеcкая обстановка',
        'note'       => '',
    ),

    array(
        'id'         => 3,
        'parent_id'  => 1,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Экологичеcкая обстановка',
        'name_max'   => 'Экологичеcкая обстановка',
        'name_min'   => 'Экологичеcкая обстановка',
        'note'       => '',
    ),


    array(
        'id'         => 4,
        'parent_id'  => 1,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Чрезвычайные ситуации',
        'name_max'   => 'Чрезвычайные ситуации',
        'name_min'   => 'Чрезвычайные ситуации',
        'note'       => '',
    ),

    array(
        'id'         => 5,
        'parent_id'  => 1,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Эксплуатация жилищного фонда',
        'name_max'   => 'Эксплуатация жилищного фонда',
        'name_min'   => 'Эксплуатация жилищного фонда',
        'note'       => '',
    ),

    array(
        'id'         => 6,
        'parent_id'  => 1,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Благоустройство и дорожное хозяйство',
        'name_max'   => 'Благоустройство и дорожное хозяйство',
        'name_min'   => 'Благоустройство и дорожное хозяйство',
        'note'       => '',
    ),

    array(
        'id'         => 7,
        'parent_id'  => 1,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Городской транспорт',
        'name_max'   => 'Городской транспорт',
        'name_min'   => 'Городской транспорт',
        'note'       => '',
    ),

    array(
        'id'         => 11,
        'parent_id'  => 1,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Жалобы и обращения по другим вопросам',
        'name_max'   => 'Жалобы и обращения по другим вопросам',
        'name_min'   => 'Жалобы и обращения по другим вопросам',
        'note'       => '',
    ),

    //-------------------
    //  Подкатегории

    array(
        'id'         => 8,
        'parent_id'  => 5,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'ГВС',
        'name_max'   => 'ГВС',
        'name_min'   => 'ГВС',
        'note'       => '',
    ),

    array(
        'id'         => 9,
        'parent_id'  => 5,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'ХВС',
        'name_max'   => 'ХВС',
        'name_min'   => 'ХВС',
        'note'       => '',
    ),

    array(
        'id'         => 10,
        'parent_id'  => 5,
        'id_pict'    => 1,
        'level_this' => 1,
        'markfordel' => false,
        'title'      => 'Водоотведение',
        'name_max'   => 'Водоотведение',
        'name_min'   => 'Водоотведение',
        'note'       => '',
    ),

);