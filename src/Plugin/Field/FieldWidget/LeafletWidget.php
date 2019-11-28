<?php

namespace Drupal\leaflet_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldDefaultWidget;
use Drupal\geofield\WktGeneratorInterface;
use Drupal\leaflet\LeafletService;
use Drupal\leaflet_widget\LeafletWidgetSettingsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the "leaflet_widget" widget.
 *
 * @FieldWidget(
 *   id = "leaflet_widget",
 *   label = @Translation("Leaflet Map"),
 *   description = @Translation("Provides a map powered by Leaflet and Leaflet.widget."),
 *   field_types = {
 *     "geofield",
 *   },
 * )
 */
class LeafletWidget extends GeofieldDefaultWidget {

  use LeafletWidgetSettingsTrait;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * LeafletWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\geofield\WktGeneratorInterface $wkt_generator
   *   The WKT format Generator service.
   * @param \Drupal\leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    GeoPHPInterface $geophp_wrapper,
    WktGeneratorInterface $wkt_generator,
    LeafletService $leaflet_service
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $geophp_wrapper,
      $wkt_generator
    );
    $this->leafletService = $leaflet_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('geofield.geophp'),
      $container->get('geofield.wkt_generator'),
      $container->get('leaflet.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    parent::settingsForm($form, $form_state);
    $form = $this->getWidgetSettingsForm($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Attach class to wkt input element, so we can find it in js/widget.js.
    $json_element_name = 'leaflet-widget-input';
    $element['value']['#attributes']['class'][] = $json_element_name;

    // Determine map settings and add map element.
    $map_settings = $this->getSetting('map');
    $input_settings = $this->getSetting('input');
    $js_settings = [];
    $map = leaflet_map_get_info($map_settings['leaflet_map']);
    $map['settings']['center'] = $map_settings['center'];
    $map['settings']['zoom'] = $map_settings['zoom'];

    if (!empty($map_settings['locate'])) {
      $js_settings['locate'] = TRUE;
      unset($map['settings']['center']);
    }

    $element['map'] = $this->leafletService->leafletRenderMap($map, [], $map_settings['height'] . 'px');
    $element['map']['#weight'] = -1;

    $element['title']['#type'] = 'item';
    $element['title']['#title'] = $element['value']['#title'];
    $element['title']['#weight'] = -2;
    $element['value']['#title'] = $this->t('GeoJson Data');

    // Build JS settings for leaflet widget.
    $js_settings['map_id'] = $element['map']['#map_id'];
    $js_settings['jsonElement'] = '.' . $json_element_name;
    $cardinality = $items->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();
    $js_settings['multiple'] = $cardinality == 1 ? FALSE : TRUE;
    $js_settings['cardinality'] = $cardinality > 0 ? $cardinality : 0;
    $js_settings['autoCenter'] = $map_settings['auto_center'];
    $js_settings['inputHidden'] = empty($input_settings['show']);
    $js_settings['inputReadonly'] = !empty($input_settings['readonly']);
    $js_settings['toolbarSettings'] = !empty($this->getSetting('toolbar')) ? $this->getSetting('toolbar') : [];
    $js_settings['scrollZoomEnabled'] = !empty($map_settings['scroll_zoom_enabled']) ? $map_settings['scroll_zoom_enabled'] : FALSE;

    // Include javascript.
    $element['map']['#attached']['library'][] = 'leaflet_widget/widget';
    // Leaflet.draw plugin.
    $element['map']['#attached']['library'][] = 'leaflet_widget/leaflet-geoman';

    // Settings and geo-data are passed to the widget keyed by field id.
    $element['map']['#attached']['drupalSettings']['leaflet_widget'] = [$element['map']['#map_id'] => $js_settings];

    // Convert default value to geoJSON format.
    if ($geom = $this->geoPhpWrapper->load($element['value']['#default_value'])) {
      $element['value']['#default_value'] = $geom->out('json');
    }

    return $element;
  }

  /**
   *
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

}
