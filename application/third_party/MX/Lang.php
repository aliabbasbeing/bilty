<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library extends the CodeIgniter CI_Lang class
 * to support modules.
 *
 * Install this file as application/third_party/MX/Lang.php
 *
 * @copyright	Copyright (c) 2015 Wiredesignz
 * @version 	5.5
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class MX_Lang extends CI_Lang
{
	public function load($langfile, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
	{	
		if (is_array($langfile)) 
		{
			foreach($langfile as $value) $this->load($value, $idiom, $return, $add_suffix, $alt_path);
			return;
		}
		
		$deft_lang = CI::$APP->config->item('language');
		$idiom = ($idiom == '') ? $deft_lang : $idiom;
	
		if (in_array($langfile.'_lang'.EXT, $this->is_loaded, TRUE))
			return $this->language;
		
		/* load the default language first */
		if ( ! $return) parent::load($langfile, $deft_lang, $return, $add_suffix, $alt_path);
		
		$module = CI::$APP->router->fetch_module();
		
		list($path, $_langfile) = Modules::find($langfile.'_lang', $module, 'language/'.$idiom.'/');
		
		if ($path === FALSE) 
		{
			if ($module) 
			{
				list($path, $_langfile) = Modules::find($langfile.'_lang', $module, 'language/'.$deft_lang.'/');
			}
			
			if ($path === FALSE) 
			{
				if ($idiom != $deft_lang) 
				{
					return parent::load($langfile, $idiom, $return, $add_suffix, $alt_path);
				}
				return;
			}
		}
		
		if ($path) 
		{
			if($lang = Modules::load_file($_langfile, $path, 'lang'))
			{
				if ($return) return $lang;
				
				$this->language = array_merge($this->language, $lang);
				$this->is_loaded[] = $langfile.'_lang'.EXT;
				unset($lang);
			}
		}
		
		return $this->language;
	}
}
