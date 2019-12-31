<?php

namespace Addon\PageThemeLayoutInfo;

defined('is_running') or die('Not an entry point...');


class Plugin{

  public $layout_info = array();

  /**
   * called via GetHead hook
   * 
   */
  public function __construct(){

    // only when logged-in
    if( !\gp\tool::LoggedIn() ){
      return;
    }

    if( $this->GetLayoutInfo() ){
      $this->ShowLayoutInfo();
      $this->ShowFrameworkInfo();
    }

    msg( 'Is Bootstrap? => ' . pre($this->IsBootstrap()) );
  }



  /**
   * Get the main version from a (SemVer) version string
   * 
   */
  public function GetMainVersion($ver_str){
    $parts    = explode('.', $ver_str); // split by dots
    $main_ver = preg_replace('/[^0-9]/', '', $parts[0]); // strip everything but numbers from 1st part
    return (int)$main_ver; // return integer
  }



   /**
   * Just echo layout info in a message box
   * @return boolean layout info available
   * 
   */
  public function ShowLayoutInfo(){
    if( empty($this->layout_info['data']) ){
      msg('The current page has no layout information');
      return false;
    }
    msg('Page theme name: ' . $this->layout_info['data']['name']);
    msg('Page layout: ' . $this->layout_info['data']['theme'] . ' (id: ' . $this->layout_info['id'] . ')');
    return true;
  }



  /**
   * Look for framework information and show the results in a message
   * @return boolean framework info available
   * 
   */
  public function ShowFrameworkInfo(){
    if( !isset($this->layout_info['data']['framework']) ){
      msg('Framework: the current theme\'s Addon.ini does not contain framework information');
      return false;
    }
    $framework_info = $this->layout_info['data']['framework'];
    if( isset($this->layout_info['data']['framework']['name']) ){
      msg('Framework name: ' . $framework_info['name'] );
      if( isset($this->layout_info['data']['framework']['version']) ){
        msg('Framework main version: ' . $this->GetMainVersion($framework_info['version']) );
        msg('Framework full version: ' . $framework_info['version'] );
      }
    }
    return true;
  }



  /**
   * Write everything we know about the current layout to $this->layout_info
   * @return boolean layout info available
   * 
   */
  public function GetLayoutInfo(){
    global $page, $config, $gpLayouts;

    $layout_info = array(
      'pagetype'    => $page->pagetype,
      'id'          => false,
      'is_default'  => false,
      'data'        => array(),
    );

    // Admin pages don't have a layout
    if( $page->pagetype === 'admin_display' ){
      $this->layout_info = $layout_info;
      return false;
    }

    if( isset($page->TitleInfo['gpLayout']) ){
      // page uses a custom layout
      $layout_info['id'] = $page->TitleInfo['gpLayout'];
      $layout_info['is_default'] = false;
    }else{
      // page uses the default layout
      $layout_info['id'] = $config['gpLayout'];
      $layout_info['is_default'] = true;
    }

    // get more info from the global $gpLayouts array
    $layout_info['data'] = $gpLayouts[$layout_info['id']];

    $this->layout_info = $layout_info;
    return true;
  }



  /**
   * Just get the current layout's Bootstrap main version quickly
   * this function can be used stand-alone and does not require the other class functions
   * @return boolean|integer false(not Bootstrap) | true(Bootstrap but no version number) | 2 | 3 | 4 ...
   */
  public function IsBootstrap(){
    global $page, $config, $gpLayouts;

    // admin pages don't have a theme/layout
    if( $page->pagetype === 'admin_display' ){
      return false;
    }

    $layout_id = isset($page->TitleInfo['gpLayout']) ?
      $page->TitleInfo['gpLayout'] :  // page uses a custom layout
      $config['gpLayout'];            // page uses the default layout

    // get more info from global $gpLayouts
    $layout_arr = $gpLayouts[$layout_id];

    // FrontEndFramework section in Addon.ini is not defined
    // or the name(key) value is not 'Bootstrap'
    if( !isset($layout_arr['framework']['name']) ||
        strtolower($layout_arr['framework']['name']) != 'bootstrap' ){
      return false;
    }

    // FrontEndFramework section in Addon.ini is defined and 
    // the name(key) value is 'Bootstrap'
    // but there is no version(key)
    if( empty($layout_arr['framework']['version']) ){
      return true;
    }

    // extract the main version from the version value
    // e.g. 4.3.1-b2 => 4
    $pieces   = explode('.', $layout_arr['framework']['version']);
    $main_ver = preg_replace('/[^0-9]/', '', $pieces[0]);

    return (int)$main_ver;
  }

}
