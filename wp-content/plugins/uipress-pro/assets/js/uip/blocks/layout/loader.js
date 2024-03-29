const { __, _x, _n, _nx } = wp.i18n;
const uipress = new window.uipClass();
export function fetchBlocks() {
  return [
    /**
     * Slide out panel
     * @since 3.0.0
     */
    {
      name: __('Modal', 'uipress-pro'),
      moduleName: 'uip-block-modal',
      description: __('Outputs a modal block with customisable content', 'uipress-pro'),
      category: __('Layout', 'uipress-pro'),
      group: 'layout',
      path: uipProPath + 'assets/js/uip/blocks/layout/modal.min.js',
      icon: 'open_in_new',
      settings: {},
      content: [],
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'buttonText',
              label: __('Trigger text', 'uipress-pro'),
              value: {
                string: __('Press me', 'uipress-pro'),
                dynamic: false,
                dynamicKey: '',
                dynamicPos: 'left',
              },
            },
            { option: 'iconSelect', label: __('Icon', 'uipress-pro') },
            { option: 'iconPosition', label: __('Icon position', 'uipress-pro') },
            {
              option: 'title',
              uniqueKey: 'modalTitle',
              label: __('Modal title', 'uipress-pro'),
              value: {
                string: __('Modal title', 'uipress-pro'),
                dynamic: false,
                dynamicKey: '',
                dynamicPos: 'left',
              },
            },
            { option: 'keyboardShortcut', label: __('Keyboard shortcut to open', 'uipress-lite') },
          ],
        },
        //Container options group
        {
          name: 'container',
          label: __('Block container', 'uipress-pro'),
          icon: 'crop_free',
          styleType: 'style',
          options: uipress.returnBlockConatinerOptions(),
        },
        //Container options group
        {
          name: 'trigger',
          label: __('Trigger style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          class: '.uip-panel-trigger',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'hover',
          label: __('Trigger hover styles', 'uipress-pro'),
          icon: 'ads_click',
          styleType: 'style',
          class: '.uip-panel-trigger:hover',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'active',
          label: __('Trigger active styles', 'uipress-pro'),
          icon: 'ads_click',
          styleType: 'style',
          class: '.uip-panel-trigger:active',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'modalTitle',
          label: __('Modal title', 'uipress-pro'),
          icon: 'title',
          styleType: 'style',
          class: '.uip-modal-title',
          options: uipress.returnDefaultOptions(),
        },
        {
          name: 'modalBody',
          label: __('Modal body', 'uipress-pro'),
          icon: 'padding',
          styleType: 'style',
          class: '.uip-modal-body',
          options: uipress.returnDefaultOptions(),
        },
        //Advanced options group
        {
          name: 'advanced',
          label: __('Advanced', 'uipress-pro'),
          icon: 'code',
          options: [
            { option: 'classes', label: __('Custom classes', 'uipress-pro') },

            {
              option: 'customCode',
              uniqueKey: 'css',
              label: __('Custom css', 'uipress-pro'),
              args: {
                language: 'css',
              },
            },
            {
              option: 'customCode',
              uniqueKey: 'js',
              label: __('Custom javaScript', 'uipress-pro'),
              args: {
                language: 'javascript',
              },
            },
          ],
        },
      ],
    },
  ];
}
