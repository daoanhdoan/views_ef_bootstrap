<?php
namespace Drupal\views_ef_bootstrap\Plugin\views\display_extender;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DefaultDisplayExtender;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views EF Fieldset display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "views_ef_bootstrap",
 *   title = @Translation("Views EF Bootsrap display extender"),
 *   help = @Translation("Views EF Bootstrap settings for this view."),
 *   no_ui = FALSE
 * )
 */
class ViewsEFBootstrap extends DefaultDisplayExtender {

  /**
   * The render object.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The constructor.
   *
   * @param array $configuration
   *   Site configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $render
   *   The render.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $render) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $render;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * Return an array of breakpoint names.
   */
  public static function getBreakpoints()
  {
    return ['xs', 'sm', 'md', 'lg', 'xl'];
  }

  public function getOptionGroups() {
    $options = [];
    $group_options = array_filter(explode("\n", $this->options['views_ef_bootstrap']['managed_groups']));
    array_walk($group_options, 'trim');
    foreach ($group_options as $name => $group_name) {
      $key = Html::cleanCssIdentifier(strtolower($group_name));
      $name = "group-{$key}";
      $options["$name"] = $group_name;
    }
    $options = ["group-0" => t('- No group -')] + $options;
    return $options;
  }


  public function getExposeItems() {
    $weight_delta = 0;
    $items = [];
    $groups = $this->options['views_ef_bootstrap']['groups'];
    $options = $this->getOptionGroups();

    foreach ($options as $name => $group_name) {
      if ($name !== 'group-0') {
        $items[$name] = [
          'label' => $group_name,
          'group' => isset($groups[$name]['group']) ? $groups[$name]['group'] : "group-0",
          'weight' => isset($groups[$name]['weight']) ? $groups[$name]['weight'] : $weight_delta,
          'depth' => isset($groups[$name]['depth']) ? $groups[$name]['depth'] : 0,
          'format' => isset($groups[$name]['format']) ? $groups[$name]['format'] : 'container',
          'bootstrap' => isset($groups[$name]['bootstrap']) ? $groups[$name]['bootstrap'] : FALSE,
          'id' => $name,
          'type' => 'group'
        ];
      }
    }

    $filters = $this->displayHandler->getHandlers('filter');
    foreach ($filters as $name => $filter) {
      if (!$filter->options['exposed']) {
        continue;
      }
      $field_label = ($filter->options['expose']['label']) ? $filter->options['expose']['label'] : $name;
      if ((bool) $filter->options['expose']['use_operator'] === TRUE) {
        $name = "{$name}_wrapper";
      }
      $items[$name] = [
        'label' => $field_label,
        'group' => isset($groups[$name]['group']) ? $groups[$name]['group'] : "group-0",
        'weight' => isset($groups[$name]['weight']) ? $groups[$name]['weight'] : $weight_delta,
        'depth' => isset($groups[$name]['depth']) ? $groups[$name]['depth'] : 0,
        'id' => $name,
        'type' => 'filter'
      ];
    }

    $sorts = $this->displayHandler->getHandlers('sort');
    foreach ($sorts as $sort_name => $sort) {
      if (!$sort->options['exposed']) {
        continue;
      }
      foreach (array('sort_by' => t('Sort by'), 'sort_order' => t('Sort order')) as $name => $label) {
        $items[$name] = [
          'label' => $label,
          'group' => isset($groups[$name]['group']) ? $groups[$name]['group'] : "group-0",
          'weight' => isset($groups[$name]['weight']) ? $groups[$name]['weight'] : $weight_delta,
          'depth' => isset($groups[$name]['depth']) ? $groups[$name]['depth'] : 0,
          'id' => $name,
          'type' => 'filter'
        ];
      }
      break;
    }

    $pager_plugin = $this->displayHandler->getPlugin('pager');
    if ($pager_plugin->options['expose']['items_per_page']) {
      $label = ($pager_plugin->options['expose']['items_per_page_label']) ? $pager_plugin->options['expose']['items_per_page_label'] : t("Items per page");
      $name = 'items_per_page';
      $items[$name] = [
        'label' => $label,
        'group' => isset($groups[$name]['group']) ? $groups[$name]['group'] : "group-0",
        'weight' => isset($groups[$name]['weight']) ? $groups[$name]['weight'] : $weight_delta,
        'depth' => isset($groups[$name]['depth']) ? $groups[$name]['depth'] : 0,
        'id' => $name,
        'type' => 'filter'
      ];
    }
    if ($pager_plugin->options['expose']['offset']) {
      $label = ($pager_plugin->options['expose']['offset_label']) ? $pager_plugin->options['expose']['offset_label'] : t("Offset");
      $name = 'offset';
      $items[$name] = [
        'label' => $label,
        'group' => isset($groups[$name]['group']) ? $groups[$name]['group'] : "group-0",
        'weight' => isset($groups[$name]['weight']) ? $groups[$name]['weight'] : $weight_delta,
        'depth' => isset($groups[$name]['depth']) ? $groups[$name]['depth'] : 0,
        'id' => $name,
        'type' => 'filter'
      ];
    }
    $name = 'actions';
    $items[$name] = [
      'label' => t('Actions'),
      'group' => isset($groups[$name]['group']) ? $groups[$name]['group'] : "group-0",
      'weight' => isset($groups[$name]['weight']) ? $groups[$name]['weight'] : $weight_delta,
      'depth' => isset($groups[$name]['depth']) ? $groups[$name]['depth'] : 0,
      'id' => $name,
      'type' => 'filter'
    ];
    $options = $this->getOptionGroups();
    foreach($items as $name => &$item) {
      if (!isset($options[$item['group']])) {
        $item['group'] = 'group-0';
        $item['depth'] = 0;
      }
      else if ($item['group'] !== 'group-0' && $item['depth'] === 0) {
        $item['depth'] = $items[$item['group']]['depth'] + 1;
      }
    }
    return $items;
  }

  public function getGroupFormats() {
    return [
      'details' => 'Details',
      'details_open' => 'Details (open)',
      'fieldset' => t('Fieldset'),
      'container' => t('Container'),
      'vertical_tabs' => t('Vertical tabs'),
      'horizontal_tabs' => t('Horizontal Tabs')
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($form_state->get('section') !== 'exposed_form_options') {
      return [];
    }

    $options = $this->options['views_ef_bootstrap'];
    $defaults = $this->getPluginDefinition();

    $form['views_ef_bootstrap'] = [
      '#tree' => TRUE,
    ];

    $form['views_ef_bootstrap']['enabled'] = [
      '#type' => 'checkbox',
      '#default_value' => isset($options['enabled']) ?
        $options['enabled'] :
        $defaults['views_ef_bootstrap']['enabled']['default'],
      '#title' => t('Enable bootstrap around exposed forms ?'),
    ];

    $form['views_ef_bootstrap']['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Exposed form bootstrap options'),
      '#states' => [
        'visible' => [
          ':input[name="views_ef_bootstrap[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    foreach ($this->getBreakpoints() as $breakpoint) {
      $breakpoint_option = "col_$breakpoint";
      $prefix = 'col' . ($breakpoint != 'xs' ? '-' . $breakpoint : '');
      $form['views_ef_bootstrap']['options'][$breakpoint_option] = [
        '#type' => 'select',
        '#title' => $this->t("Column width of items at '$breakpoint' breakpoint"),
        '#default_value' => $options['options'][$breakpoint_option],
        '#description' => $this->t("Set the number of columns each item should take up at the '$breakpoint' breakpoint and higher."),
        '#options' => [
          'none' => 'None (or inherit from previous)',
          $prefix => 'Equal',
          $prefix . '-auto' => 'Fit to content',
        ],
      ];
      foreach ([1, 2, 3, 4, 6, 12] as $width) {
        $form['views_ef_bootstrap']['options'][$breakpoint_option]['#options'][$prefix . "-$width"] = $this->formatPlural(12 / $width, '@width (@count column per row)', '@width (@count columns per row)', ['@width' => $width]);
      }
    }

    $form['views_ef_bootstrap']['managed_groups'] = array(
      '#type' => 'textarea',
      '#title' => 'Groups',
      '#description' => 'Enter a list of groups to include in this form',
      '#default_value' => $this->options['views_ef_bootstrap']['managed_groups'],
    );

    $form['views_ef_bootstrap']['groups'] = [
      '#type' => 'table',
      '#header' => array(t('Filter'), t('Format'), t('Bootstrap'), t('Group'), t('Weight')),
      '#attributes' => [
        'class' => ['views_ef_bootstrap-filter-overview'],
        'id' => 'vefg-table',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'filter-weight',
        ],
        [
          'action' => 'depth',
          'relationship' => 'group',
          'group' => 'filter-depth',
          'hidden' => FALSE,
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'filter-group',
          'subgroup' => 'filter-group',
          'source' => 'filter-name',
          'hidden' => TRUE,
          'limit' => FALSE,
        ]
      ]
    ];

    $items = $this->getExposeItems();dpm($items);
    $rows = $this->buildRows($items);

    foreach ($rows as $row) {
      $item = $row['#item'];
      $form['views_ef_bootstrap']['groups'][$item['id']] = $row;
    }
  }

  public function buildRows(&$elements, $rootParentID = 'group-0', $depth = -1) {
    $branch = [];
    $depth++;
    foreach ($elements as $name => $item) {
      if ($item['group'] !== $rootParentID) {
        continue;
      }
      if ($item['depth'] >= $depth && $depth < 5) {
        $item['depth'] = $depth;
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $item['depth'],
        ];
        $element['#item'] = $item;
        $element['item'] = [
          '#prefix' => !empty($indentation) ? \Drupal::service('renderer')->render($indentation) : '',
          '#markup' => ($item['type'] == 'group') ? "<strong>" . $item['label'] . "</strong>" : $item['label'],
          'id' => [
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => ['class' => 'filter-name'],
            '#parents' => ['views_ef_bootstrap', 'groups', $name, 'id']
          ],
          'depth' => [
            '#type' => 'hidden',
            '#default_value' => $item['depth'],
            '#attributes' => [
              'class' => ['filter-depth'],
            ],
            '#parents' => ['views_ef_bootstrap', 'groups', $name, 'depth']
          ],
        ];
        if ($item['type'] === 'group') {
          $element['format'] = array(
            '#type' => 'select',
            '#placeholder' => 'Render groups as',
            '#title' => 'Render groups as',
            '#title_display' => 'invisible',
            '#options' => $this->getGroupFormats(),
            '#default_value' => $item['format'],
            '#parents' => ['views_ef_bootstrap', 'groups', $name, 'format'],
          );
        }
        else {
          $element['format'] = ['#plain_text' => ''];
        }
        if ($item['type'] === 'group') {
          $element['bootstrap'] = array(
            '#type' => 'checkbox',
            '#title' => 'Apply bootstrap to items in group',
            '#title_display' => 'invisible',
            '#default_value' => $item['bootstrap'],
            '#parents' => ['views_ef_bootstrap', 'groups', $name, 'bootstrap'],
          );
        }
        else {
          $element['bootstrap'] = ['#plain_text' => ''];
        }

        $element['group'] = array(
          '#type' => 'select',
          '#title' => 'Group for ' . $item['label'],
          '#options' => $this->getOptionGroups(),
          '#title_display' => 'invisible',
          '#default_value' => $item['group'],
          '#attributes' => [
            'class' => ['filter-group'],
          ],
        );
        $element['weight'] = array(
          '#type' => 'weight',
          '#title' => "Weight for " . $item['label'],
          '#title_display' => 'invisible',
          '#default_value' => $item['weight'],
          '#attributes' => [
            'class' => ['filter-weight'],
          ],
        );

        $element += [
          '#attributes' => ['class' => ['draggable']],
          '#weight' => $item['weight']
        ];
        $branch[] = $element;
        $childrens = $this->buildRows($elements, $item['id'], $depth);
        if ($childrens) {
          $branch = array_merge($branch, $childrens);
        }
      }
    }
    // Automatically get sorted results.
    usort($form, [SortArray::class, 'sortByWeightElement']);
    return empty($branch) ? [] : $branch;
  }

  public function getBootstrapClasses() {
    $options = $this->options['views_ef_bootstrap']['options'];
    $classes = [];
    foreach (ViewsEFBootstrap::getBreakpoints() as $breakpoint) {
      if ($options["col_$breakpoint"] == 'none') {
        continue;
      }
      $classes[] = $options["col_$breakpoint"];
    }
    return $classes;
  }


  public function buildGroups($groups, &$form) {
    $elements = [];
    foreach($groups as $group) {
      $item = $group['item'];
      $element = [];
      if ($item['type'] === 'group' && !empty($group['children'])) {
        if ($item['format'] == 'vertical_tabs' || $item['format'] == 'horizontal_tabs') {
          $element = array(
            '#type' => $item['format'],
            '#parents' => [$item['id']]
          );
        } else if ($item['format'] == 'details' || $item['format'] == 'details_open') {
          $element = array(
            '#type' => 'details',
            '#open' => ($item['format'] == 'details_open') ? TRUE : FALSE
          );
        }
        else {
          $element = array(
            '#type' => $item['format']
          );
        }
        $element += [
          '#item' => $item,
          '#weight' => $item['weight'],
          '#title' => $item['label'],
          '#attributes' => [
            'class' => ['vefg', 'vefg-' . Html::cleanCssIdentifier($item['format']), 'clearfix'],
            'id' => 'edit-' . Html::cleanCssIdentifier($item['id'])
          ]
        ];
        $element += $this->buildGroups($group['children'], $form);
      }
      else if($item['type'] === 'filter') {
        $element = $form[$item['id']];
        $element['#item'] = $item;
        $element['#weight'] = $item['weight'];
        $element['#title'] = $item['label'];
        unset($form[$item['id']]);
      }
      if (!empty($element)) {
        if ($item['group'] !== 'group-0' && !empty($form['#items'][$item['group']]['bootstrap'])) {
          $classes = $this->getBootstrapClasses();
          if ($item['type'] === 'filter') {
            $element_wrapper = [
              '#type' => 'container',
              '#attributes' => ['class' => $classes],
              $item['id'] => $element
            ];
            $element = $element_wrapper;
          }
          else {
            $element['#attributes']['class'] = array_merge($element['#attributes']['class'], $classes);
          }
        }
        $elements[$item['id']] = $element;
      }
    }
    return $elements;
  }

  public function buildTreeData(&$elements, $rootParentID = 'group-0', $depth = -1) {
    $branch = [];
    $depth++;
    foreach ($elements as $key => $element) {
      if ($element['group'] !== $rootParentID) {
        continue;
      }
      if ($element['depth'] >= $depth && $depth < 5) {
        $element['depth'] = $depth;
        $branch[] = [
          'item' => $element,
          'children' => $this->buildTreeData($elements, $element['id'], $depth),
        ];
      }
    }
    // Automatically get sorted results.
    usort($branch, [get_class(), 'sortByWeight']);
    return empty($branch) ? [] : $branch;
  }

  /**
   * Internal function used to sort array items by weight.
   *
   * @param array $a
   *   First element.
   * @param array $b
   *   Second element.
   *
   * @return int
   *   The weight.
   */
  public static function sortByWeight(array $a, array $b) {
    if ($a['item']['weight'] === $b['item']['weight']) {
      return 0;
    }

    return $a['item']['weight'] < $b['item']['weight'] ? -1 : 1;
  }
  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $options = parent::defineOptions();

    $options['views_ef_bootstrap'] = [
      'enabled' => ['default' => FALSE, 'bool' => TRUE],
      'options' => [],
      'managed_groups' => ['default' => 'Filters'],
      'groups' => []
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Only process options if this is an unrelated form.
    /*if ($form_state->get('section') === 'exposed_form_options') {
      $views_ef_bootstrap = $form_state->getValue('views_ef_bootstrap');
      foreach ($views_ef_bootstrap['options']['sort'] as $key => $data) {
        $data += $data['item'];
        unset($data['item']);
        $views_ef_bootstrap['options']['sort'][$key] = $data;
      }

      $this->options['views_ef_bootstrap'] = $views_ef_bootstrap;
    }*/
    parent::submitOptionsForm($form, $form_state);
  }

}
