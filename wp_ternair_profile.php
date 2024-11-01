<?php
/*
Plugin Name: Ternair Profile Widget
Plugin URI: http://www.ternair.com
Description: Customize the page based on Ternair Profile output
Author: Ternair
Version: 1.1.6
Author URI: http://www.ternair.com
*/

// Block direct requests

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}


function tp_render() {
	//global $wpdb; // this is how you get access to the database
	$number = stripslashes( $_POST['number'] );

	$dummy = new wp_ternair_profile();
	//$content = array('conent' =>  );
	//$array_we_send_back = array( 'test' => "Test" );

	//echo json_encode( $array_we_send_back );

	$dummy->renderContent( $number );
	//echo wp_json_encode('LOL ');
	//echo $wp_ternair_profile->renderContent();
	wp_die(); // this is required to terminate immediately and return a proper response
}

function myplugin_ajaxurl() {

	echo '<script type="text/javascript">
           var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";

    function tp_getFingerPrint() {
       try {
                return getFingerprint();
            
        } catch (err) { return "default"; }
    }
    function setFingerPrint()
    {            
      var cookiestring = "fingerprint="+tp_getFingerPrint(); 
      document.cookie = cookiestring;
    }              
    setFingerPrint();  
    </script>';
}

function loadFingerPrint() {
	wp_enqueue_script( 'tp_fp', '//services.crmservice.eu/scripts/universal_fp2.js' );
}

add_action( "wp_enqueue_scripts", 'loadFingerPrint' );
add_action( 'wp_head', 'myplugin_ajaxurl' );
add_action( 'wp_ajax_tp_render', 'tp_render' );
add_action( 'wp_ajax_nopriv_tp_render', 'tp_render' );
define( 'TERNAIR_TP_FILE', __FILE__ );
define( 'TERNAIR_TP_PATH', plugin_dir_path( __FILE__ ) );


add_action( 'widgets_init',
	function () {
		register_widget( 'wp_ternair_profile' );
	} );

// opties init zodat we deze kunnen gebruiken
function admin_init2() {
	register_setting( 'tp_list_options', 'tp-reg', 'validate' );
}

function validate( $input ) {
	if ( strlen( $input ) == 0 ) {
		add_settings_error( 'tp_list_options', // Setting title
			'tp_key_texterror', // Error ID
			'Please enter a valid key', // Error message
			'error'// Type of message
		);

		return null;
	}
	$regKey   = $input;
	$instance = (int) substr( $regKey, - 1 );
	if ( intval( $instance ) == 0 ) {
		add_settings_error( 'tp_list_options', // Setting title
			'tp_key_texterror', // Error ID
			'Please enter a valid key', // Error message
			'error'
		);

		return null;
	}

	return $input;
}

class wp_ternair_profile extends WP_Widget {
	protected $option_name2 = 'tp-reg';

	/** constructor  */
	public function __construct() {
		parent::__construct( false, $name = 'Ternair Profile' );
		add_action( 'admin_init', 'admin_init2' );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		register_activation_hook( TERNAIR_TP_FILE, array( $this, 'activate' ) );
	}


	public function validate( $input ) {

		$valid                    = array();
		$valid['registrationKey'] = sanitize_text_field( $input['registrationKey'] );
		if ( strlen( $valid['registrationKey'] ) == 0 ) {
			add_settings_error( 'tp_list_options', // Setting title
				'tp_key_texterror', // Error ID
				'Please enter a valid key', // Error message
				'error'// Type of message
			);
		}

		$regKey   = $valid['registrationKey'];
		$instance = substr( $regKey, - 1 );
		if ( intval( $instance ) == 0 ) {
			add_settings_error( 'tp_list_options', // Setting title
				'tp_key_texterror', // Error ID
				'Please enter a valid key', // Error message
				'error'
			);
		}

		return $valid;
	}

	//item in settingsmenu toevoegen
	public function add_page() {

		add_options_page( 'Ternair Profile Settings', 'Ternair Profile Settings', 'manage_options', 'tp_list_options', array(
			$this,
			'options_do_page'
		) );
	}

	public function options_do_page() {

		$options2 = get_option( $this->option_name2 );

		?>
		<div class="wrap">
			<h2>Ternair Profile Settings</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'tp_list_options' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Registrationkey:</th>
						<td><input type="text" class="widefat" id="<?php
							echo $this->option_name2 ?>" name="<?php
							echo $this->option_name2 ?>" value="<?php
							echo $options2; ?>"/></td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php
					_e( 'Save Changes' ) ?>"/>
				</p>
			</form>
		</div>
		<?php
	}

	public function activate() {
		update_option( $this->option_name2, $this->data );
	}

	public function deactivate() {
		delete_option( $this->option_name2 );
	}


	public function widget( $args, $instance ) {
		$title = '';
		extract( $args );
		if ( isset( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', sanitize_text_field( $instance['title'] ) );
		}

		echo $before_widget;

		if ( isset( $_GET['tid'] ) && ! empty( $_GET['tid'] ) ) {
			$tid = sanitize_text_field( $_GET['tid'] );
		}

		if ( isset( $_COOKIE['fingerprint'] ) && ! empty( $_COOKIE['fingerprint'] ) ) {
			$fingerprint = sanitize_text_field( $_COOKIE['fingerprint'] );
		}
		if ( isset( $tid ) ) {
			echo '<script type="text/javascript">var cookiestring = "tid=' . $tid . '";document.cookie = cookiestring;</script>'; //cookie overschrijven
			$_COOKIE['tid'] = $tid;
		} else {
			if ( isset( $_COOKIE['tid'] ) && ! empty( $_COOKIE['tid'] ) ) {
				$tid = sanitize_text_field( $_COOKIE['tid'] );
			}
		}

		if ( ! isset( $tid ) && ! isset( $fingerprint ) ) {

			echo '<script type="text/javascript">' .
				 'jQuery(document).ready(function( $ ) {
                                     jQuery.post({
                                     url: ajaxurl,
                                     dataType: "html", 
                                     data: { "action":"tp_render", "number" : "' . $this->number . '" },
                              success: function(result){
                                  //do something with the results of the shortcode to the DOM
                                  jQuery("#' . $this->id . '").html(result);
                              },
                              error: function(errorThrown){console.log(errorThrown);}
                }); });// end of ajax

 </script>';

		} else {
			$this->renderContent( $this->number );
		}

		echo $after_widget;
	}

	public function renderContent( $number ) {

		$widget_options_all = get_option( $this->option_name );
		$instance           = $widget_options_all[ $number ];
		$rules              = array();
		$defaultContent     = null;
		$selectionId        = null;
		$startCode          = null;
		$keyParameters      = null;

		if ( isset( $instance['defaultContent'] ) ) {
			$defaultContent = $instance['defaultContent'];
		}

		if ( isset( $instance['selectionId'] ) ) {
			$selectionId = (int) $instance['selectionId'];
		}

		if ( isset( $instance['startCode'] ) ) {
			$startCode = sanitize_text_field( $instance['startCode'] );
		}

		if ( isset( $instance['parameters'] ) ) {
			$keyParameters = sanitize_text_field( $instance['parameters'] );
		}

		if ( isset( $instance['rules'] ) ) {
			$rules = $instance['rules'];
		}

		$params  = explode( ',', $keyParameters );
		$tpKey   = null;
		$tpValue = null;
		foreach ( $params as $key ) {
			//key voor TP samenstellen
			if ( isset( $_COOKIE[ $key ] ) ) {
				if ( ! isset( $tpKey ) ) {
					$tpValue = $_COOKIE[ $key ];
					$tpKey   = $startCode . '_' . trim( $key, ' ' );
				}
			}
		}

		$rendered = 0;
		if ( ! is_null( $rules ) ) {
			//echo serialize($rules);

			foreach ( $rules as $key2 ) {
				$keyName    = sanitize_text_field( $key2["name"] );
				$keyValue   = sanitize_text_field( $key2["value"] );
				$keyContent = $key2["content"];
				if ( current_user_can( 'edit_posts' ) ) { //alleen for editmode en admins
					echo '<div class="ternair-profile-widget"><div style="color:red">where <span style="font-weight:bold;">' . $keyName . '</span> equals <span style="font-weight:bold;">' . $keyValue . '</span></div></div>';
					echo '<div class="ternair-profile-widget"  style="border: solid 1px red;">' . $keyContent . '</div>';
				}

				$regKey = get_option( $this->option_name2 );

				if ( isset( $selectionId ) && isset( $tpKey ) && isset( $tpValue ) && isset( $regKey ) ) {
					$tpCall   = $this->call_tp_api( $selectionId, $tpKey, $tpValue, $regKey );
					$tpResult = json_decode( $tpCall, true );

					if ( isset( $tpResult ) ) {

						if ( strpos( $keyName, '/' ) )  //hierarchial
						{
							$keyNames = explode( '/', $keyName );
							foreach ( $keyNames as $name ) {
								if ( array_key_exists( $name, $tpResult ) ) {
									$tpValue = $tpResult[ $name ];
								}

								if ( $tpValue && is_array( $tpValue ) ) {
									foreach ( $tpValue as $tp ) {
										if ( array_key_exists( $name, $tpValue ) ) {
											if ( $tpValue[ $name ] == $tp ) {
												echo apply_filters( 'the_content', $keyContent );
												$rendered ++;
											}
										}
									}
								}

							}
						} else {

							if ( array_key_exists( $keyName, $tpResult ) ) {
								$keyNameValue = $tpResult[ $keyName ];
								$isBool       = gettype( $keyNameValue ) == 'boolean';

								if ( $isBool ) {
									$keyNameValue = $keyNameValue ? 'true' : 'false';
								}


								if ( $keyNameValue == $keyValue ) {
									echo apply_filters( 'the_content', $keyContent );
									$rendered ++;
								}
							}
						}
					}
				}
			}
		}

		if ( $rendered == 0 ) //indien geen itemn gerenderd, dan default renderen
		{
			echo apply_filters( 'the_content', $defaultContent );
		}
	}

	function call_tp_api( $selectionId, $startCode, $input, $regKey ) {
		if ( $regKey ) {

			$instanceId  = (int) substr( $regKey, - 1 );
			$service_url = apply_filters( 'ternair_custom_service_url', 'https://campaign' . $instanceId . '-profile-api.ternairsoftware.com/Flowchart/Calculation' );

			$new_service_url = add_query_arg(
				[
					'selectionId' => $selectionId,
					'startCode'   => $startCode,
					'input'       => $input,
					'regKey'      => $regKey,
				],
				$service_url
			);

			$response = wp_remote_get( $new_service_url, array(
				'headers' => array(
					'Authorization' => 'Token ' . $regKey,
					'Content-Type'  => 'application/json'
				),
			) );


			$body = wp_remote_retrieve_body( $response );

			return $body;
		}

		return null;
	}

	public function update( $new_instance, $old_instance ) {
		$rules = null;
		if ( isset( $_POST['rules'] ) ) {
			$rules = stripslashes_deep( $_POST['rules'] );
		}

		//regels ophalen
		$instance                = $old_instance;
		$instance['title']       = sanitize_text_field( $new_instance['title'] );
		$instance['selectionId'] = (int) strip_tags( $new_instance['selectionId'] );
		$instance['startCode']   = sanitize_text_field( $new_instance['startCode'] );
		$instance['parameters']  = sanitize_text_field( $new_instance['parameters'] );

		if ( isset( $new_instance['ruleKey'] ) ) {
			$instance['ruleKey'] = sanitize_text_field( $new_instance['ruleKey'] );
		}

		$instance['defaultContent'] = trim( stripslashes( $new_instance['defaultContent'] ) );
		$instance['rules']          = $rules;

		return $instance;
	}

	public function form( $instance ) {
		$title          = '';
		$selectionId    = '';
		$startCode      = '';
		$keyParameters  = 'tid,fingerprint';
		$defaultContent = '';

		if ( isset( $instance['title'] ) ) {
			$title = sanitize_text_field( $instance['title'] );
		}
		if ( isset( $instance['selectionId'] ) ) {
			$selectionId = (int) esc_attr( $instance['selectionId'] );
		}

		if ( isset( $instance['startCode'] ) ) {
			$startCode = sanitize_text_field( $instance['startCode'] );
		}

		if ( isset( $instance['parameters'] ) ) {
			$keyParameters = sanitize_text_field( $instance['parameters'] );
		}

		if ( isset( $instance['defaultContent'] ) ) {
			$defaultContent = esc_attr( $instance['defaultContent'] );
		}

		$ruleLength = 0;
		if ( isset( $instance['rules'] ) ) {
			$rules      = $instance['rules'];
			$ruleLength = count( $rules );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title: (optional)' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'selectionId' ); ?>"><?php _e( 'Selectie id:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'selectionId' ); ?>" name="<?php echo $this->get_field_name( 'selectionId' ); ?>" type="text" value="<?php echo $selectionId; ?>"/>
		</p>
		<p>
			<label for="<?php
			echo $this->get_field_id( 'startCode' ); ?>"><?php
				_e( 'Startcode' ); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'startCode' ); ?>" name="<?php
			echo $this->get_field_name( 'startCode' ); ?>" type="text" value="<?php
			echo $startCode; ?>"/>
		</p>
		<p>
			<label for="<?php
			echo $this->get_field_id( 'parameters' ); ?>"><?php
				_e( 'Parameters' ); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'parameters' ); ?>" name="<?php
			echo $this->get_field_name( 'parameters' ); ?>" type="text" value="<?php
			echo $keyParameters; ?>"/>
		</p>
		<div class="rule-container">
			<p>Rules:</p>
			<div class="addinput">
				<?php
				$r = 1;
				while ( $r <= $ruleLength ) {
					$rule = $rules[ $r ];
					echo '<div class="rule-item" style="padding: 2px;border: solid 1px #eee;margin-bottom: 10px;">' . '<input type="text" style="width:45%" id="rules[' . $r . '][name]" name="rules[' . $r . '][name]" value="' . $rule['name'] . '" placeholder="Key" />' . '<span style="width: 10%;text-align: center;display: inline-block;">=</span>' . '<input type="text" id="rules[' . $r . '][value]" style="width:45%" name="rules[' . $r . '][value]" value="' . $rule['value'] . '" placeholder="Value" />' . '<div><textarea style="width:99%;margin-top:10px;" id="rules[' . $r . '][content]" name="rules[' . $r . '][content]">' . $rule['content'] . '</textarea></div>' . '<div style="text-align:right;"><a href="#" class="remNew">Remove</a></div></div>';
					$r ++;
				}

				?>
			</div>
			<script type="text/javascript">
				var $ternair = jQuery.noConflict();
				$ternair(function () {

					$ternair('[id*="wp_ternair_profile"] input').unbind('change');

					var i = $ternair('.rule-container .rule-item').length + 1;
					$ternair('.ternairAddNew').on('click', function () {
						var parentWidget = $ternair(this).parents('.widget:first');
						var addDiv = $ternair('.addinput', parentWidget);
						$ternair('<div class="rule-item" style="padding: 2px;border: solid 1px #eee;margin-bottom: 10px;"><input type="text" style="width:45%" id="rules[' + i + '][name]" name="rules[' + i + '][name]" value="" placeholder="Key" />' +
							'<span style="width: 10%;text-align: center;display: inline-block;">=</span>' +
							'<input type="text" id="rules[' + i + '][value]" style="width:45%" name="rules[' + i + '][value]" value="" placeholder="Value" />' +
							'<div><textarea style="width:99%;margin-top:10px;" id="rules[' + i + '][content]" name="rules[' + i + '][content]"></textarea></div>' +
							'<div style="text-align:right;"><a href="#" class="remNew">Remove</a></div></div>').appendTo(addDiv);
						i++;
						return false;
					});
					$ternair('.addinput', '[id*="wp_ternair_profile"]').on('click', '.remNew', function () {

						$ternair(this).parents('.rule-item').remove();
						jQuery('.rule-item input').trigger('change');
						i--;
						return false;
					});
				});

			</script>
			<div style="text-align:right;margin-bottom:20px;">
				<button type="button" class="ternairAddNew">Add rule</button>
			</div>
		</div>
		<p>
			<label for="<?php
			echo $this->get_field_id( 'defaultContent' ); ?>"><?php
				_e( 'Default content' ); ?></label>
			<textarea class="widefat" id="<?php
			echo $this->get_field_id( 'defaultContent' ); ?>" name="<?php
			echo $this->get_field_name( 'defaultContent' ); ?>"> <?php
				echo $defaultContent; ?> </textarea>
		</p>
		<?php
	}

}