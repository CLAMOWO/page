const { __, _x, _n, _nx } = wp.i18n;
const uipress = new window.uipClass();
export function fetchBlocks() {
  return [
    /**
     * Text input block
     * @since 3.0.0
     */
    {
      name: __('Date range', 'uipress-pro'),
      moduleName: 'uip-date-range',
      description: __('Outputs a date picker that can be either a single date or date range.', 'uipress-pro'),
      category: __('Form', 'uipress-pro'),
      group: 'form',
      path: uipProPath + 'assets/js/uip/blocks/inputs/date-range.min.js',
      icon: 'date_range',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'inputLabel',
              label: __('Label', 'uipress-pro'),
              value: {
                string: __('Text input', 'uipress-pro'),
              },
            },
            {
              option: 'textField',
              uniqueKey: 'inputName',
              label: __('Meta key', 'uipress-pro'),
              args: { metaKey: true },
            },
            {
              option: 'title',
              uniqueKey: 'inputPlaceHolder',
              label: __('Placeholder', 'uipress-pro'),
              value: {
                string: __('Placeholder text...', 'uipress-pro'),
              },
            },
            { option: 'trueFalse', uniqueKey: 'dateRange', label: __('Date range', 'uipress-pro') },
            { option: 'trueFalse', uniqueKey: 'inputRequired', label: __('Required field', 'uipress-pro') },
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
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'inputStyle',
          label: __('Input style', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-date-input',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'label',
          label: __('Label', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-input-label',
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

    /**
     * Text area block
     * @since 3.0.0
     */
    {
      name: __('Checkbox', 'uipress-pro'),
      moduleName: 'uip-checkbox-input',
      description: __('A checkbox block with support for multiple options. For use with the form block', 'uipress-pro'),
      category: __('Form', 'uipress-pro'),
      group: 'form',
      path: uipProPath + 'assets/js/uip/blocks/inputs/checkbox-input.min.js',
      icon: 'check_box',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'inputLabel',
              label: __('Label', 'uipress-pro'),
              value: {
                string: __('Select', 'uipress-pro'),
              },
            },
            {
              option: 'textField',
              uniqueKey: 'inputName',
              label: __('Meta key', 'uipress-pro'),
              args: { metaKey: true },
            },

            {
              option: 'selectOptionCreator',
              uniqueKey: 'selectOptions',
              label: __('Select options', 'uipress-pro'),
            },

            { option: 'trueFalse', uniqueKey: 'inputRequired', label: __('Required field', 'uipress-pro') },
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
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'inputStyle',
          label: __('Select style', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-input',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'label',
          label: __('Label', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-input-label',
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
    /**
     * Radio checkbox
     * @since 3.0.0
     */
    {
      name: __('Radio', 'uipress-pro'),
      moduleName: 'uip-radio-input',
      description: __('A radio block with support for multiple options. For use with the form block', 'uipress-pro'),
      category: __('Form', 'uipress-pro'),
      group: 'form',
      path: uipProPath + 'assets/js/uip/blocks/inputs/radio-input.min.js',
      icon: 'radio_button_checked',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'inputLabel',
              label: __('Label', 'uipress-pro'),
              value: {
                string: __('Select', 'uipress-pro'),
              },
            },
            {
              option: 'textField',
              uniqueKey: 'inputName',
              label: __('Meta key', 'uipress-pro'),
              args: { metaKey: true },
            },

            {
              option: 'selectOptionCreator',
              uniqueKey: 'selectOptions',
              label: __('Select options', 'uipress-pro'),
            },

            { option: 'trueFalse', uniqueKey: 'inputRequired', label: __('Required field', 'uipress-pro') },
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
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'inputStyle',
          label: __('Select style', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-input',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'label',
          label: __('Label', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-input-label',
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

    /**
     * Image
     * @since 3.0.0
     */
    {
      name: __('Image select', 'uipress-pro'),
      moduleName: 'uip-image-select-input',
      description: __('Outputs a image select input. For use with the form block', 'uipress-pro'),
      category: __('Form', 'uipress-pro'),
      group: 'form',
      path: uipProPath + 'assets/js/uip/blocks/inputs/image-select-input.min.js',
      icon: 'image',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'inputLabel',
              label: __('Label', 'uipress-pro'),
              value: {
                string: __('Select', 'uipress-pro'),
              },
            },
            {
              option: 'textField',
              uniqueKey: 'inputName',
              label: __('Meta key', 'uipress-pro'),
              args: { metaKey: true },
            },

            { option: 'trueFalse', uniqueKey: 'inputRequired', label: __('Required field', 'uipress-pro') },
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
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'inputStyle',
          label: __('Select area', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-image-select',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'label',
          label: __('Label', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-input-label',
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

    /**
     * Colour select
     * @since 3.0.0
     */
    {
      name: __('Colour select', 'uipress-pro'),
      moduleName: 'uip-colour-select-input',
      description: __('Outputs a colour select input. For use with the form block', 'uipress-pro'),
      category: __('Form', 'uipress-pro'),
      group: 'form',
      path: uipProPath + 'assets/js/uip/blocks/inputs/colour-select-input.min.js',
      icon: 'palette',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            {
              option: 'title',
              uniqueKey: 'inputLabel',
              label: __('Label', 'uipress-pro'),
              value: {
                string: __('Select', 'uipress-pro'),
              },
            },
            {
              option: 'textField',
              uniqueKey: 'inputName',
              label: __('Meta key', 'uipress-pro'),
              args: { metaKey: true },
            },

            {
              option: 'title',
              uniqueKey: 'inputPlaceHolder',
              label: __('Placeholder', 'uipress-lite'),
              value: {
                string: __('Select colour...', 'uipress-lite'),
              },
            },

            { option: 'trueFalse', uniqueKey: 'inputRequired', label: __('Required field', 'uipress-pro') },
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
          name: 'style',
          label: __('Style', 'uipress-pro'),
          icon: 'palette',
          styleType: 'style',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'inputStyle',
          label: __('Select area', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-image-select',
          options: uipress.returnDefaultOptions(),
        },
        //Container options group
        {
          name: 'label',
          label: __('Label', 'uipress-pro'),
          icon: 'label',
          styleType: 'style',
          class: '.uip-input-label',
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
