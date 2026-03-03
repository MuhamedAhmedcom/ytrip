<?php
namespace MuhamedAhmed;

if (!defined('ABSPATH')) exit;

/**
 * ✅ Universal Meta Handler for CodeStar Framework
 * 
 * يدعم جميع أنواع الحقول:
 * - Text, Textarea, WP Editor
 * - Select, Checkbox, Radio
 * - Upload, Gallery, Media
 * - Group, Repeater
 * - Serialized & Unserialized data
 * 
 * @version 2.0
 */
class Meta {
    
    private static $instance = null;
    private static $debug_mode = false;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$debug_mode = isset($_GET['dev']) || isset($_GET['debug']);
        }
        return self::$instance;
    }
    
    /**
     * ✅ جلب أي حقل من CodeStar Framework
     * يدعم: text, textarea, wp_editor, group, repeater, serialized, etc.
     * 
     * @param string $field_id معرف الحقل
     * @param int|null $post_id معرف المنشور (null للحصول تلقائياً)
     * @param mixed $default القيمة الافتراضية
     * @return mixed قيمة الحقل
     * 
     * أمثلة الاستخدام:
     * 
     * // 1. حقول نصية بسيطة (text, textarea):
     * $phone = Meta::field('phone', $post_id);
     * $email = Meta::field('email', $post_id);
     * 
     * // 2. محرر WordPress (wp_editor):
     * $bio = Meta::field('lawyer_bio', $post_id);
     * 
     * // 3. مجموعة (group) - حسابات التواصل:
     * $social = Meta::field('social_accounts', $post_id);
     * if (is_array($social)) {
     *     foreach ($social as $account) {
     *         echo $account['platform_name'];
     *         echo $account['platform_url'];
     *         echo $account['platform_icon'];
     *     }
     * }
     * 
     * // 4. حقل واحد من داخل المجموعة:
     * $social = Meta::field('social_accounts', $post_id);
     * $first_platform = $social[0]['platform_name'] ?? '';
     * 
     * // 5. تكرار (repeater):
     * $items = Meta::field('repeater_field', $post_id);
     * foreach ($items as $item) {
     *     echo $item['sub_field'];
     * }
     * 
     * // 6. صورة (upload):
     * $image = Meta::field('profile_image', $post_id);
     * // Returns: array with 'url', 'id', 'alt', etc.
     * 
     * // 7. معرض صور (gallery):
     * $gallery = Meta::field('image_gallery', $post_id);
     * foreach ($gallery as $image) {
     *     echo '<img src="' . $image['url'] . '">';
     * }
     * 
     * // 8. Checkbox متعدد:
     * $options = Meta::field('checkbox_field', $post_id);
     * // Returns: array of selected values
     * 
     * // 9. مع قيمة افتراضية:
     * $city = Meta::field('city', $post_id, 'الرياض');
     */
    public static function field($field_id, $post_id = null, $default = '') {
        // جلب معرف المنشور تلقائياً إذا لم يتم تحديده
        if (!$post_id) {
            global $wp_query;
            $post_id = $wp_query->queried_object_id ?? get_the_ID();
        }
        
        if (!$post_id) {
            return $default;
        }
        
        // محاولة جلب القيمة مباشرة
        $value = get_post_meta($post_id, $field_id, true);
        
        // محاولة مع underscore إذا لم توجد
        if (empty($value)) {
            $value = get_post_meta($post_id, '_' . $field_id, true);
        }
        
        // فك التسلسل إذا كانت البيانات مسلسلة
        if (is_string($value) && self::isSerialized($value)) {
            $value = maybe_unserialize($value);
        }
        
        // Debug mode
        if (self::$debug_mode) {
            error_log("Meta::field($field_id, $post_id) = " . print_r($value, true));
        }
        
        return !empty($value) ? $value : $default;
    }
    
    /**
     * Alias للتوافق مع الإصدارات السابقة
     */
    public static function get($field_id, $post_id = null, $default = '') {
        return self::field($field_id, $post_id, $default);
    }
    
    /**
     * ✅ جلب حقل فرعي من داخل Group
     * 
     * مثال:
     * $platform_name = Meta::getGroupField('social_accounts', 0, 'platform_name', $post_id);
     */
    public static function getGroupField($group_id, $index, $sub_field, $post_id = null, $default = '') {
        $group = self::field($group_id, $post_id);
        
        if (is_array($group) && isset($group[$index][$sub_field])) {
            return $group[$index][$sub_field];
        }
        
        return $default;
    }
    
    /**
     * ✅ جلب جميع حقول Repeater
     * 
     * مثال:
     * $items = Meta::getRepeaterFields('services', $post_id);
     */
    public static function getRepeaterFields($repeater_id, $post_id = null) {
        $value = self::field($repeater_id, $post_id, []);
        return is_array($value) ? $value : [];
    }
    
    /**
     * ✅ التحقق من وجود قيمة للحقل
     */
    public static function has($field_id, $post_id = null) {
        $value = self::field($field_id, $post_id);
        
        if (is_array($value)) {
            return !empty($value);
        }
        
        if (is_string($value)) {
            return trim($value) !== '';
        }
        
        return !empty($value);
    }
    
    /**
     * ✅ جلب Taxonomy Terms
     */
    public static function getTerms($post_id, $taxonomy, $field = 'name') {
        $terms = get_the_terms($post_id, $taxonomy);
        
        if (!$terms || is_wp_error($terms)) {
            return [];
        }
        
        if ($field === 'names') {
            return wp_list_pluck($terms, 'name');
        }
        
        if ($field === 'name') {
            return $terms[0]->name ?? '';
        }
        
        return $terms;
    }
    
    /**
     * ✅ التحقق من التسلسل
     */
    private static function isSerialized($data) {
        if (!is_string($data)) {
            return false;
        }
        
        $data = trim($data);
        
        if ($data === 'N;') {
            return true;
        }
        
        if (!preg_match('/^([adObis]):/', $data, $matches)) {
            return false;
        }
        
        switch ($matches[1]) {
            case 'b':
            case 'i':
            case 'd':
                return (bool) preg_match("/^{$matches[1]}:[0-9.E+-]+;$/", $data);
            case 's':
                return (bool) preg_match("/^{$matches[1]}:[0-9]+:\".*/s", $data);
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$matches[1]}:[0-9]+:/s", $data);
        }
        
        return false;
    }
}

Meta::init();
