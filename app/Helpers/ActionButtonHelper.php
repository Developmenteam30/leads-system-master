<?php

namespace App\Helpers;

class ActionButtonHelper
{
    public static function actionbuttons($actions): string
    {
        $buttons =  '<div class="btn-group">
            <button type="button" class="btn btn-primary" style="">View</button>';

        if (count($actions) > 0) {
            $buttons .= '
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-mdb-toggle="dropdown" aria-expanded="false" style="">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu" style="">';

            foreach ($actions as $action) {
                $buttons .= '<li><a class="dropdown-item" href="#" data-mdb-number="'.htmlspecialchars($action['id']).'>'.$action['type'].'</a></li>';
            }

            $buttons .= '</ul>
                </ul>';
        }

        $buttons .= '</div>';

        return $buttons;
    }

    public static function deleteOrRestore($item): string
    {
        if ($item->trashed()) {
            return '<button title="Undelete" class="restore-btn btn btn-primary btn-sm ms-2" data-mdb-number="'.htmlspecialchars($item->id).'">Restore</button>';
        } else {
            return self::delete($item);
        }
    }

    public static function delete($item): string
    {
        return '<button title="Delete" class="delete-btn btn btn-danger btn-sm ms-2" data-mdb-number="'.htmlspecialchars($item->id).'">Delete</button>';
    }

    public static function edit($item): string
    {
        return '<button title="Edit" class="edit-btn btn btn-primary btn-sm ms-2" data-mdb-number="'.htmlspecialchars($item->id).'">Edit</button>';
    }

    public static function view($item): string
    {
        return '<button title="View" class="view-btn btn btn-primary btn-sm" data-mdb-number="'.htmlspecialchars($item->id).'">View</button>';
    }

    public static function upload($item): string
    {
        return '<button title="File Upload" class="upload-btn btn btn-primary btn-sm ms-2" data-mdb-number="'.htmlspecialchars($item->id).'">Upload</button>';
    }
}
