<?php
/**
 * @package		##package##
 * @subpackage	##subpackage##
 * @author		##author##
 * @copyright 	##copyright##
 * @license		##license##
 * @version		##version##
 */
 
// no direct access
defined( 'ZEN_ALLOW' ) or die( 'Restricted access' );


/**
 * Get settings sent from Admin
 *
 *
 */

	$jinput = JFactory::getApplication()->input;
	$admin = $jinput->get('admin');
	
	if($admin) {
		$framework_enable = $jinput->get('framework_enable');
		$compressed = $jinput->get('compress');
		$enable_template_css = $jinput->get('template_css');
		$style_name = $jinput->get('style_name', '', 'RAW');
		$style_name = strtolower(str_replace(' ', '-', $style_name));
		$fontawesome_type = $jinput->get('fontawesome_type');
		$variables = $jinput->get('variables', '', 'RAW');
		$variables = explode('___', $variables);
		$settings = $jinput->get('settings', '', 'RAW');
		$settings = explode('___', $settings);
		$addtocompiler = $jinput->get('custom_less', '', 'RAW');
		$animate = $jinput->get('animate');
		$animations = $jinput->get('animations', '', 'RAW');
		$rowstyles = $jinput->get('rowstyles', '', 'RAW');
		
	
	// Define template name
	if (!defined('TEMPLATE')) {
		define( 'TEMPLATE', basename(dirname(dirname(dirname(__FILE__)))));
	}
	
	jimport('joomla.filesystem.file');
	jimport('joomla.filesystem.path');
	
	
	/**
	 * Import LESS PHP
	 *
	 *
	 */
	
		require_once JPath::clean(__DIR__ . '/../libs/lessc/Less.php');
	
	
	
	/**
	 * Paths
	 *
	 *
	 */
		$sitepath = str_replace('\\', '/', JPATH_SITE);
		$lessBasePath = JPATH_SITE . '/templates/' . $this->template . '/less';
		$path = $sitepath . '/templates/' . TEMPLATE;
		$relative_path = JUri::base().'/templates/' . $this->template;
	
	
	/**
	 * 	When the load template.css is enabled we create the template.css based on the current settings
	 *	Lets just check to see if the template.css file exists early int he file
	 *	and return if it does to save some time.
	 */
	 	
	 	$write_css = 0;
	 	
		if($enable_template_css =="1") {
			if (file_exists($path . '/css/template.css')) {
			    echo '<div class="alert alert-success"><p>Template.css is now enabled. Current theme styling will be bypassed.</p></div>';
			    
			    return;
			    
			} else {
				$write_css = 1;
			}
		}
		
		
	
	// Only parse the admin settings if not creating css
	if(!$enable_template_css) {
	
	/**
	 * Variables
	 *
	 *
	 */
	
		$variable_array = array();
	
		// Process Colour Variables
		foreach ($variables as $variable) {
	
			
			$variable = explode('||', $variable);
	
			if(isset($variable[1])) {
	
				
				
				// First three letters
				// Because Hex values like ddd and aaa
				// Also look like darken and auto
				$threeletters = substr($variable[1], 0, 3);
				
				if($threeletters =="non") {
					$variable[1] = 'transparent';
				}
				
				$firstletter = substr($variable[1], 0, 1);
								
				if (trim($variable[1]) !== '') {
					$param = $variable[0].'';
					
					// Checks to see if using transparent, inherit, auto, lighten or darken
					if($firstletter =="@" || $firstletter =="l" || $firstletter =="t" || $firstletter =="i") {
					
						if($firstletter == "l") {
							
							// We can lighten # or variables so need to readd # if its a hex
							$variable = explode('(', $variable[1]);
							
							// Check for first letter of variable we are looking at
							$firstletter = substr($variable[1], 0, 1);
							
							// If its a variable just do the normal process
							if($firstletter == '@') {
								$value = $variable[0].'('.$variable[1];
							} else {
								//other wise it must be a colour so we add back the #
								$value = $variable[0].'(#'.$variable[1];
							}
						}
						else {	
							$value = $variable[1];
						}
					
					} elseif($threeletters =="dar" || $threeletters =="aut") {
							
							// We can lighten # or variables so need to readd # if its a hex
							$variable = explode('(', $variable[1]);
							
							// Check for first letter of variable we are looking at
							$firstletter = substr($variable[1], 0, 1);
							
							// If its a variable just do the normal process
							if($firstletter == '@') {
								$value = $variable[0].'('.$variable[1];
							} else {
								//other wise it must be a colour so we add back the #
								$value = $variable[0].'(#'.$variable[1];
							}
							
					} elseif($threeletters =="rgb") {
					
						$value =  $variable[1];
					} else {
						$value = '#'.$variable[1];
					}
					
					$variable_array[$param] = $value;
				}
			}
		}
		
	
		// Process relevant settings
		foreach ($settings as $variable) {
	
			$variable = explode('||', $variable);
		
			if(isset($variable[1])) {
				$param = ltrim($variable[0], '_') .'';
				$value = $variable[1];
				$variable_array[$param] = $value;
			}
		}

		// Adds path variable for when including nested images in styles folder
		$variable_array['@path'] = "'../../'";

	
	/**
	 * Declare Files Array
	 *
	 *
	 */
	
	
		$files = array();
	
	
	
	
	/**
	 * Bootstrap
	 *
	 *
	 */
	
	 if($framework_enable) {
	
	 	// Get Framework version
	 	$framework_version = $jinput->get('framework_version');
	
	 	// Get Bootstrap files to compile
	 	$framework_files = $jinput->get('framework_files','','CMD');
	 	$framework_files = explode('_', $framework_files);
	
	 	$write_framework_file = "";
	 	$write_framework_file .= '@import "variables.less";'."\n";
	
	 	if($framework_version !=="uikit") {
	 		$write_framework_file .= '@import "mixins.less";'."\n";
	 	}
	
	 	foreach ($framework_files as $key => $file) {
	 		if($file !=="") {
	 			$write_framework_file .= '@import "'.$file.'.less";'."\n";
	 		}
	 	}
	
	 	file_put_contents(JPATH_SITE . '/templates/' . $this->template.'/zengrid/libs/frameworks/'.$framework_version.'/less/'.$framework_version.'.less', $write_framework_file);
	
	 	$files[] =  '../zengrid/libs/frameworks/'.$framework_version.'/less/'.$framework_version.'.less';
	 }
	
	
	
	/**
	 * Animate CSS
	 *
	 *
	 */
	
		// Animate css
		if($animate) {
			
			$files[] = '../zengrid/libs/zengrid/less/animate/animate.less';
			
			$animations = explode(',', $animations);
			$animations = array_unique($animations);
			
			foreach ($animations as $key => $animation) {
				if($animation !=="" && $animation !=="none") {
					$files[] =  '../zengrid/libs/zengrid/less/animate/'.$animation.'.less';
				}	
			}
		}	
		
		
		
		/**
		 * Zenmenu
		 *
		 *
		 */
		
			if($fontawesome_type) {
				$files[] =  '../zengrid/libs/zengrid/less/fontawesome/font-awesome-'.$fontawesome_type.'.less';
			}
			
	
	
	
	/**
	 * Main Template
	 *
	 *
	 */
	
	// Main Theme less File
	$files[] = 'template.less';
	
	
	
	/**
	 * Row Styles CSS
	 *
	 *
	 */
		
		$rowstyles = array_filter(explode(',', $rowstyles));
	
		if(is_array($rowstyles)) {
		
			$row_styles = JPATH_SITE . '/templates/' . $this->template . '/less/styles';
			$row_path = 'styles';
			if(is_dir($row_styles)) {
				
				foreach ($rowstyles as $key => $row_file) {
				
					$files[] = $row_path.'/'.$row_file.'.less';
						
				}
			}	
		
		}
		
		
	
	/**
	 * Extra Less files added
	 *
	 *
	 */
	
		
	
		if($addtocompiler !=="") {
			$addtocompiler = rtrim($addtocompiler, ",");
			$addtocompiler = explode(',', $addtocompiler);
			
			foreach ($addtocompiler as $key => $file) {
				$files[] = $file;
			}
			
		}
		
		
	
	/**
	 * Custom Less File
	 *
	 *
	 */
	
		$customless = $path . '/less/custom.less';
		    
        if(file_exists($customless)) {
            $files[] = 'custom.less';
        }
	
	
	
	
	/**
	 * Create generated template.less file to parse
	 *
	 *
	 */
	
	$files_to_compile = '// This file is automatically generated by the Zen Grid Framework. Do not edit';
	$files_to_compile .= "\n";
	$files_to_compile .= "\n";
	
	foreach ($files as $key => $file) {
		$files_to_compile .= '@import "';
		$files_to_compile .= $file;
		$files_to_compile .= '";';
		$files_to_compile .= "\n";
	}
	
	file_put_contents($lessBasePath.'/template-generated.less', $files_to_compile);
	
	
	/**
	 * Start Compression and Loop through the array
	 *
	 *
	 */

		$options = array(
		    'sourceMap'         => true,
		    'sourceMapWriteTo'  => $path . '/css/theme.'.$style_name.'.map',
		    'sourceMapURL'      => $relative_path.'/css/theme.'.$style_name.'.map',
		   	'sourceMapRootpath'	=> '../',
		   	'sourceMapBasepath'   => str_replace('\\', '/', JPATH_SITE) . '/templates/' . TEMPLATE
		);
		
		if($compressed) {
			$options['compress'] = 'true';
		}
		
		$parser = new Less_Parser( $options );
		$parser->parseFile( $path . '/less/template-generated.less', '');
		$parser->ModifyVars($variable_array);
		$css = $parser->getCss();
	
		
		file_put_contents($path . '/css/theme.'.$style_name.'.css', $css);
		
		echo '<div class="alert alert-success"><h4 class="alert-heading">Message</h4><p>Less has been compiled to css.</p></div>';
	
		
		// Create gzipped version
			$gzip = '<?php ob_start ("ob_gzhandler");
			    header("Content-type: text/css; charset: UTF-8");
			    header("Cache-Control: must-revalidate");
			    $offset = 60 * 60 ;
			    $ExpStr = "Expires: " .
			    gmdate("D, d M Y H:i:s",
			    time() + $offset) . " GMT";
			    header($ExpStr);?>';
		
			$gzip .= $css;
		
			file_put_contents($path . '/css/theme.'.$style_name.'.php', $gzip);
			
		
	}
		
		
		/**
		 * 	 
		 *	If creating template.css for first time
		 *
		 */
		 
		 
		if($enable_template_css =="1" && $write_css) {
		
			
			
				// Main Theme less File
				$files[] = $lessBasePath.'/template.less';
				
				
				/**
				 * Start Compression and Loop through the array
				 *
				 *
				 */
			
								
				$options = array(
				    'sourceMap'         => true,
				    'sourceMapWriteTo'  => $path . '/css/theme.'.$style_name.'.map'
				);
			
				if($compressed) {
					$options['compress'] = 'true';
				}
			
				$parser = new Less_Parser( $options );
			
				$css = "";
				foreach ($files as $key => $file) {
				
					
					$parser->parseFile( $file,'');
					$parser->ModifyVars($variable_array);
					$css .= $parser->getCss();
					$parser->reset();
				}
				
				
				
		    file_put_contents($path . '/css/template.css', $css);
		    
		    echo '<div class="alert alert-success"><h4 class="alert-heading">Message</h4><p>Template.css has been created.</p></div>';
		    
		} 
	} else {
		header("Location: ".JUri::base());
		die();
	}