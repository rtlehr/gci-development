<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'full_path',
        'path_ids',
        'depth',
        'status',
        'notes',
    ];

    public function parent()
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    public function rebuildHierarchyFields(): void
    {
        if ($this->parent_id) {
            $parent = self::find($this->parent_id);

            $this->full_path = $parent
                ? $parent->full_path . ' / ' . $this->name
                : $this->name;

            $this->path_ids = $parent
                ? $parent->path_ids . '/' . $this->id
                : (string) $this->id;

            $this->depth = $parent
                ? $parent->depth + 1
                : 0;
        } else {
            $this->full_path = $this->name;
            $this->path_ids = (string) $this->id;
            $this->depth = 0;
        }

        $this->saveQuietly();
    }

    public function rebuildDescendantHierarchyFields(): void
    {
        $this->load('children');

        foreach ($this->children as $child) {
            $child->rebuildHierarchyFields();
            $child->rebuildDescendantHierarchyFields();
        }
    }

    public function wouldCreateCircularParent(int $newParentId): bool
    {
        if ($this->id === $newParentId) {
            return true;
        }

        $parent = self::find($newParentId);

        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }

            $parent = $parent->parent;
        }

        return false;
    }
}