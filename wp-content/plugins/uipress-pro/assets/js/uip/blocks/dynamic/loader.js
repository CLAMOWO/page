const { __, _x, _n, _nx } = wp.i18n;
const uipress = new window.uipClass();
export function fetchBlocks() {
  return [
    /**
     * Group date range
     * @since 3.0.0
     */
    {
      name: __('Grouped Date range', 'uipress-pro'),
      moduleName: 'uip-grouped-date-range',
      description: __("Outputs a grouped date picker. This date picker is used for controlling it's siblings range such as analytic blocks.", 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'dynamic',
      path: uipProPath + 'assets/js/uip/blocks/dynamic/group-date-range.min.js',
      icon: 'event',
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
              uniqueKey: 'inputPlaceHolder',
              label: __('Placeholder', 'uipress-pro'),
              value: {
                string: __('Placeholder text...', 'uipress-pro'),
              },
            },
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
        //Container options group
        {
          name: 'contentArea',
          label: __('Group area', 'uipress-pro'),
          icon: 'dashboard_customize',
          styleType: 'style',
          class: '.uip-date-group-area',
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
     * Media library
     * @since 3.0.0
     */
    {
      name: __('Media library', 'uipress-pro'),
      moduleName: 'uip-media-library',
      description: __('Outputs a media library, with upload, delete and folder features', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'dynamic',
      path: uipProPath + 'assets/js/uip/blocks/dynamic/media-library.min.js',
      icon: 'photo_library',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            { option: 'trueFalse', uniqueKey: 'limitToAuthor', label: __('Only show users own content?', 'uipress-pro') },
            { option: 'number', uniqueKey: 'photosPerPage', label: __('Photos per page', 'uipress-pro'), value: 20 },
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
          name: 'attachmentArea',
          label: __('Image area', 'uipress-lite'),
          icon: 'image',
          styleType: 'style',
          class: '.uip-attachment-area',
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
     * Plugin updates
     * @since 3.0.0
     */
    {
      name: __('Plugin updates', 'uipress-pro'),
      moduleName: 'uip-plugin-updates',
      description: __('Outputs a list of available plugin updates and allows you update from the block', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'dynamic',
      path: uipProPath + 'assets/js/uip/blocks/dynamic/plugin-updates.min.js',
      icon: 'upgrade',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [],
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
     * Plugin updates
     * @since 3.0.0
     */
    {
      name: __('Plugin search', 'uipress-pro'),
      moduleName: 'uip-plugin-search',
      description: __('Search the plugin directory with quick filters, discover new plugins and install all from one block', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'dynamic',
      path: uipProPath + 'assets/js/uip/blocks/dynamic/plugin-search.min.js',
      icon: 'extension',
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
              uniqueKey: 'inputPlaceHolder',
              label: __('Placeholder', 'uipress-pro'),
              value: {
                string: __('Search plugins...', 'uipress-pro'),
              },
            },
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
          name: 'search',
          label: __('Search Input', 'uipress-pro'),
          icon: 'input',
          styleType: 'style',
          class: '.uip-search-input',
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
     * User meta
     * @since 3.0.2
     */
    {
      name: __('User meta', 'uipress-pro'),
      moduleName: 'uip-user-meta-block',
      description: __('Outputs selected user meta, either as a string or an list of values', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'dynamic',
      path: uipProPath + 'assets/js/uip/blocks/dynamic/user-meta.min.js',
      icon: 'manage_accounts',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            { option: 'userMetaSelect', label: __('Meta to output', 'uipress-pro') },
            {
              option: 'choiceSelect',
              uniqueKey: 'listDirection',
              label: __('List direction (for array values)', 'uipress-lite'),
              args: {
                options: [
                  {
                    value: 'vertical',
                    label: __('Vertical', 'uipress-lite'),
                  },
                  {
                    value: 'horizontal',
                    label: __('Horizontal', 'uipress-lite'),
                  },
                ],
              },
              value: {
                value: 'vertical',
              },
            },
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
          name: 'metaItem',
          label: __('Meta value', 'uipress-pro'),
          icon: 'settings',
          styleType: 'style',
          class: '.uip-meta-item',
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
    ///
    ///Content navigator
    ///
    {
      name: __('Content Navigator', 'uipress-pro'),
      moduleName: 'uip-content-navigator',
      description: __('Creates a navigatable file tree of all your site content like posts, pages and media. Allows for creation of new folders and organisation of content', 'uipress-pro'),
      category: __('Dynamic', 'uipress-pro'),
      group: 'dynamic',
      path: uipProPath + 'assets/js/uip/blocks/dynamic/content-navigator.min.js',
      icon: 'folder_open',
      settings: {},
      optionsEnabled: [
        //Block options group
        {
          name: 'block',
          label: __('Block options', 'uipress-pro'),
          icon: 'check_box_outline_blank',
          options: [
            { option: 'trueFalse', uniqueKey: 'limitToAuthor', label: __('Only show users own content?', 'uipress-pro') },
            { option: 'searchPostTypes', label: __('Post types available in navigator ', 'uipress-pro') },
            {
              option: 'choiceSelect',
              uniqueKey: 'defaultLink',
              label: __('Default link', 'uipress-lite'),
              args: {
                options: [
                  {
                    value: 'editPost',
                    label: __('Edit post', 'uipress-lite'),
                  },
                  {
                    value: 'viewPost',
                    label: __('View post', 'uipress-lite'),
                  },
                ],
              },
              value: {
                value: 'editPost',
              },
            },
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
