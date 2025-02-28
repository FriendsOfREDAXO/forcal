<?php
/**
 * @author Your Name
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Manager;

use rex;
use rex_sql;
use rex_user;

class forCalUserCategoryManager
{
    /**
     * Get categories assigned to a user
     * 
     * @param int $userId
     * @return array
     */
    public static function getUserCategories($userId = null)
    {
        if ($userId === null && rex::getUser()) {
            $userId = rex::getUser()->getId();
        }
        
        if (!$userId) {
            return [];
        }

        $sql = rex_sql::factory();
        $query = 'SELECT category_id FROM ' . rex::getTablePrefix() . 'forcal_user_categories WHERE user_id = :user_id';
        $sql->setQuery($query, ['user_id' => $userId]);
        
        $categories = [];
        foreach ($sql->getArray() as $row) {
            $categories[] = $row['category_id'];
        }
        
        return $categories;
    }

    /**
     * Check if user can view all categories
     * 
     * @param int $userId
     * @return bool
     */
    public static function canViewAllCategories($userId = null)
    {
        if ($userId === null && rex::getUser()) {
            $userId = rex::getUser()->getId();
        }
        
        if (!$userId) {
            return false;
        }

        // Admins can always view all categories
        if (rex::getUser() && rex::getUser()->isAdmin()) {
            return true;
        }

        $sql = rex_sql::factory();
        $query = 'SELECT forcal_view_all_categories FROM ' . rex::getTablePrefix() . 'user WHERE id = :user_id';
        $sql->setQuery($query, ['user_id' => $userId]);
        
        if ($sql->getRows() > 0) {
            return (bool)$sql->getValue('forcal_view_all_categories');
        }
        
        return false;
    }

    /**
     * Get categories a user is allowed to view/edit
     * (returns all categories if user has view_all_categories permission)
     * 
     * @param int $userId
     * @return array
     */
    public static function getAllowedCategories($userId = null)
    {
        if (self::canViewAllCategories($userId)) {
            // Get all categories
            $sql = rex_sql::factory();
            $sql->setQuery('SELECT id FROM ' . rex::getTablePrefix() . 'forcal_categories WHERE status = 1');
            
            $categories = [];
            foreach ($sql->getArray() as $row) {
                $categories[] = $row['id'];
            }
            
            return $categories;
        }
        
        return self::getUserCategories($userId);
    }

    /**
     * Assign categories to a user
     * 
     * @param int $userId
     * @param array $categoryIds
     * @return bool
     */
    public static function assignCategoriesToUser($userId, array $categoryIds)
    {
        if (!$userId) {
            return false;
        }
        
        $sql = rex_sql::factory();
        
        // Begin transaction
        $sql->beginTransaction();
        
        try {
            // Delete existing assignments
            $query = 'DELETE FROM ' . rex::getTablePrefix() . 'forcal_user_categories WHERE user_id = :user_id';
            $sql->setQuery($query, ['user_id' => $userId]);
            
            // Insert new assignments
            foreach ($categoryIds as $categoryId) {
                $insertSql = rex_sql::factory();
                $insertSql->setTable(rex::getTablePrefix() . 'forcal_user_categories');
                $insertSql->setValue('user_id', $userId);
                $insertSql->setValue('category_id', $categoryId);
                $insertSql->insert();
            }
            
            // Commit transaction
            $sql->commit();
            return true;
        } catch (\Exception $e) {
            // Rollback on error
            $sql->rollBack();
            return false;
        }
    }

    /**
     * Update user's view_all_categories setting
     * 
     * @param int $userId
     * @param bool $viewAll
     * @return bool
     */
    public static function setViewAllCategories($userId, $viewAll)
    {
        if (!$userId) {
            return false;
        }
        
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTablePrefix() . 'user');
        $sql->setWhere(['id' => $userId]);
        $sql->setValue('forcal_view_all_categories', $viewAll ? 1 : 0);
        
        try {
            $sql->update();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
