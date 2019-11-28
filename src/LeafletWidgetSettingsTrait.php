<?php

namespace Drupal\leaflet_widget;

/**
 * Trait LeafletWidgetSettingsTrait.
 *
 * @package Drupal\leaflet_widget
 */
trait LeafletWidgetSettingsTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $base_layers = self::getLeafletMaps();
    return [
      'map' => [
        'leaflet_map' => array_shift($base_layers),
        'height' => 300,
        'center' => [
          'lat' => 0.0,
          'lon' => 0.0,
        ],
        'auto_center' => TRUE,
        'zoom' => 10,
        'locate' => TRUE,
        'scroll_zoom_enabled' => TRUE,
      ],
      'input' => [
        'show' => TRUE,
        'readonly' => FALSE,
      ],
      'toolbar' => [
        'position' => 'topright',
        'drawMarker' => TRUE,
        'drawPolyline' => TRUE,
        'drawRectangle' => TRUE,
        'drawPolygon' => TRUE,
        'drawCircle' => FALSE,
        'drawCircleMarker' => FALSE,
        'editMode' => TRUE,
        'dragMode' => TRUE,
        'cutPolygon' => FALSE,
        'removalMode' => TRUE,
      ],
    ];
  }

  /**
   *
   */
  public function getWidgetSettingsForm(array $form) {
    $map_settings = $this->getSetting('map');
    $form['map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Settings'),
    ];
    $form['map']['leaflet_map'] = [
      '#title' => $this->t('Leaflet Map'),
      '#type' => 'select',
      '#options' => ['' => $this->t('-- Empty --')] + $this->getLeafletMaps(),
      '#default_value' => $map_settings['leaflet_map'],
      '#required' => TRUE,
    ];
    $form['map']['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $map_settings['height'],
    ];
    $form['map']['center'] = [
      '#type' => 'fieldset',
      '#collapsed' => TRUE,
      '#collapsible' => TRUE,
      '#title' => 'Default map center',
    ];
    $form['map']['center']['lat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      '#default_value' => $map_settings['center']['lat'],
      '#required' => TRUE,
    ];
    $form['map']['center']['lon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Longtitude'),
      '#default_value' => $map_settings['center']['lon'],
      '#required' => TRUE,
    ];
    $form['map']['auto_center'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically center map on existing features'),
      '#description' => t("This option overrides the widget's default center."),
      '#default_value' => $map_settings['auto_center'],
    ];
    $form['map']['zoom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default zoom level'),
      '#default_value' => $map_settings['zoom'],
      '#required' => TRUE,
    ];
    $form['map']['locate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically locate user current position'),
      '#description' => t("This option centers the map to the user position."),
      '#default_value' => $map_settings['locate'],
    ];
    $form['map']['scroll_zoom_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Scroll Wheel Zoom on click'),
      '#description' => t("This option enables zooming by mousewheel as soon as the user clicked on the map."),
      '#default_value' => $map_settings['scroll_zoom_enabled'],
    ];

    $input_settings = $this->getSetting('input');
    $form['input'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geofield Settings'),
    ];
    $form['input']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show geofield input element'),
      '#default_value' => $input_settings['show'],
    ];
    $form['input']['readonly'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make geofield input element read-only'),
      '#default_value' => $input_settings['readonly'],
      '#states' => [
        'invisible' => [
          ':input[name="fields[field_geofield][settings_edit_form][settings][input][show]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $toolbar_settings = $this->getSetting('toolbar');

    $form['toolbar'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Leaflet PM Settings'),
    ];

    $form['toolbar']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Toolbar position.'),
      '#options' => [
        'topleft' => 'topleft',
        'topright' => 'topright',
        'bottomleft' => 'bottomleft',
        'bottomright' => 'bottomright',
      ],
      '#default_value' => $toolbar_settings['position'],
    ];

    $form['toolbar']['drawMarker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw markers.'),
      '#default_value' => $toolbar_settings['drawMarker'],
    ];
    $form['toolbar']['drawPolyline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw polyline.'),
      '#default_value' => $toolbar_settings['drawPolyline'],
    ];

    $form['toolbar']['drawRectangle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw rectangle.'),
      '#default_value' => $toolbar_settings['drawRectangle'],
    ];

    $form['toolbar']['drawPolygon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw polygon.'),
      '#default_value' => $toolbar_settings['drawPolygon'],
    ];

    $form['toolbar']['drawCircle'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Adds button to draw circle. (unsupported by GeoJSON'),
      '#default_value' => $toolbar_settings['drawCircle'],
    ];

    $form['toolbar']['drawCircleMarker'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Adds button to draw circle marker. (unsupported by GeoJSON'),
      '#default_value' => $toolbar_settings['drawCircleMarker'],
    ];

    $form['toolbar']['editMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to toggle edit mode for all layers.'),
      '#default_value' => $toolbar_settings['editMode'],
    ];

    $form['toolbar']['dragMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to toggle drag mode for all layers.'),
      '#default_value' => $toolbar_settings['dragMode'],
    ];

    $form['toolbar']['cutPolygon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to cut hole in polygon.'),
      '#default_value' => $toolbar_settings['cutPolygon'],
    ];

    $form['toolbar']['removalMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to remove layers.'),
      '#default_value' => $toolbar_settings['removalMode'],
    ];

    return $form;
  }

  /**
   * Get maps available for use with Leaflet.
   */
  protected static function getLeafletMaps() {
    $options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $options[$key] = $map['label'];
    }
    return $options;
  }

}
