<?php 

class theme_academi_core_renderer extends core_renderer {

    protected function render_custom_menu(custom_menu $menu) {

        $mycourses = $this->page->navigation->get('coursecategory');

        if (isloggedin() && $mycourses && $mycourses->has_children()) {
            $branchlabel = get_string('coursecategory');
            $branchurl   = new moodle_url('/course');
            $branchtitle = $branchlabel;
            $branchsort  = 10000;

            $branch = $menu->add($branchlabel, $branchurl, $branchtitle, $branchsort);

            foreach ($mycourses->children as $coursenode) {
                $branch->add($coursenode->get_content(), $coursenode->action, $coursenode->get_title());
            }
        }

        return parent::render_custom_menu($menu);
    }

    // protected function render_custom_menu_item(custom_menu_item $menunode) {
    //     $transmutedmenunode = new theme_academi_transmuted_custom_menu_item($menunode);
    //     return parent::render_custom_menu_item($transmutedmenunode);
    // }

    public function custom_button() {
        return '<button><a href="' . new moodle_url('/') . '">Klik Saya</a></button>';
    }

}
 ?>