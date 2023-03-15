const uipCommandOptions = {
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      translations: uipTranslations,
      masterPrefs: uipMasterPrefs,
      defaults: uipDefaults,
      userPreferences: uipUserPrefs,
      uipToolbar: "",
      floatingActive: false,
    };
  },
  watch: {},
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {},
  methods: {},
  template: '<command-search :defaults="defaults" :options="masterPrefs" :translations="translations" :preferences="userPreferences"></command-search>',
};
const uipCommand = uipVue.createApp(uipCommandOptions);

uipCommand.component("command-search", {
  props: {
    defaults: Object,
    options: Object,
    translations: Object,
    preferences: Object,
  },
  data: function () {
    return {
      loading: true,
      floating: false,
      quickActions: [],
      fetchQuickAactions: false,
      os: "mac",
      search: {
        open: false,
        term: "",
        perPage: 20,
        currentPage: 1,
        results: [],
        totalFound: 0,
        categorized: [],
        nothingFound: false,
        searching: false,
        activeIndex: 0,
      },
      contextual: {
        open: false,
        actions: [],
        item: [],
      },
    };
  },
  mounted: function () {
    this.loading = false;
    let self = this;
    let isMac = navigator.platform.toUpperCase().indexOf("MAC") >= 0;
    if (!isMac) {
      self.os = "pc";
    }

    window.addEventListener("keydown", function (e) {
      if (self.search.open) {
        ///CMD D

        if (e.keyCode == 68 && (e.ctrlKey || e.metaKey)) {
          e.preventDefault();
          self.searchDirectory();
        }

        self.handleArrowKeys(e);
        if (e.keyCode == 13) {
          let item = self.search.categorized[self.search.activeIndex];

          if (self.search.nothingFound) {
            self.searchDirectory();
            return;
          }

          if (self.contextual.open) {
            self.handleContextualAction(self.search.activeIndex);
          } else {
            if (item.type == "quickAction") {
              self.handleQuickAaction(self.search.activeIndex);
            } else if (item.type == "menu") {
              self.handleMenuAaction(self.search.activeIndex);
            } else {
              self.handleContextual(self.search.activeIndex);
            }
          }
        }
      }
      if (e.keyCode == 75 && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        if (self.search.open) {
          self.search.open = false;
        } else {
          self.openThisComponent();
        }
      }

      if (e.key == "Escape") {
        if (self.contextual.open) {
          self.contextual.open = false;
        } else {
          self.search.open = false;
        }
      }

      if (self.contextual.open) {
        ///CMD E
        if (e.keyCode == 69 && (e.ctrlKey || e.metaKey)) {
          self.openThisComponent();
        }
        ///CMD O
        if (e.keyCode == 79 && (e.ctrlKey || e.metaKey)) {
          self.openThisComponent();
        }
      }
    });
  },
  watch: {
    "search.term": function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.masterSearch();
      }
    },
  },
  computed: {
    searchedCats() {
      return this.search.categorized;
    },
  },
  methods: {
    handleArrowKeys(e) {
      let self = this;
      let max = self.search.categorized.length;
      if (self.contextual.open) {
        max = self.contextual.actions.length;
      }
      if (e.keyCode == 37) {
        ///LEFT
        self.contextual.open = false;
      }
      if (e.keyCode == 40) {
        ///down
        if (self.search.activeIndex != max - 1) {
          self.search.activeIndex += 1;
          let element = document.getElementById("found-item-" + self.search.activeIndex);
          element.scrollIntoView(false, { behavior: "smooth" });
        }
      }
      if (e.keyCode == 38) {
        ///up
        if (self.search.activeIndex > 0) {
          self.search.activeIndex = self.search.activeIndex - 1;
          let element = document.getElementById("found-item-" + self.search.activeIndex);
          element.scrollIntoView(false, { behavior: "smooth" });
        }
      }
    },
    handleContextualAction(index) {
      let self = this;
      let item = self.contextual.actions[index];
      if (item.type == "link") {
        document.getElementById("uip-contextual-link-" + index).click();
      }

      if (item.type == "action") {
        if (item.action == "delete") {
          if (!confirm(self.translations.confirmDelete)) {
            return;
          }
          self.deleteItem();
        }
        if (item.action == "duplicate") {
          if (!confirm(self.translations.confirmCopy)) {
            return;
          }
          self.copyItem();
        }
        if (item.action == "deactivate_plugin" || item.action == "activate_plugin" || item.action == "delete_plugin" || item.action == "upgrade_plugin" || item.action == "install_plugin") {
          self.modifyPluginStatus(item.action);
        }
      }
    },
    modifyPluginStatus(action) {
      let self = this;
      let item = self.contextual.item;

      if (action == "delete_plugin") {
        if (!confirm(self.translations.confirmPluginDelete)) {
          return;
        }
      }

      self.search.searching = true;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_modify_plugin_status",
          security: uip_ajax.security,
          item: item,
          plugin_action: action,
        },
        success: function (response) {
          self.search.searching = false;
          if (response) {
            if (action == "upgrade_plugin") {
              try {
                data = JSON.parse(response);
                if (data.error) {
                  uipNotification(data.message, "danger");
                }
              } catch (error) {
                uipNotification(self.translations.pluginUpdated, "danger");
                self.setContextual(item);
                return;
              }
            }
            data = JSON.parse(response);
            if (data.error) {
              uipNotification(data.message, "danger");
            } else {
              uipNotification(data.message, "success");

              if (action != "delete_plugin") {
                self.setContextual(item);
              } else {
                self.masterSearch();
                self.contextual.open = false;
              }
            }
          }
        },
      });
    },
    searchDirectory() {
      let self = this;

      let searchString = this.search.term;
      searchString = searchString.toLowerCase();
      self.search.searching = true;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_search_wp_directory",
          security: uip_ajax.security,
          term: searchString,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            self.search.searching = false;
            if (data.error) {
              uipNotification(data.message, "danger");
            } else {
              if (data.plugins.length > 0) {
                self.search.categorized = data.plugins;
                self.search.nothingFound = false;
              }
              uipNotification(data.message, "success");
            }
          }
        },
      });
    },
    deleteItem() {
      let self = this;
      let item = self.contextual.item;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_delete_item_from_command",
          security: uip_ajax.security,
          id: item.id,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            if (data.error) {
              uipNotification(data.message, "danger");
            } else {
              uipNotification(data.message, "success");
              self.masterSearch();
              self.contextual.open = false;
            }
          }
        },
      });
    },
    copyItem() {
      let self = this;
      let item = self.contextual.item;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_duplicate_item_from_command",
          security: uip_ajax.security,
          id: item.id,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            if (data.error) {
              uipNotification(data.message, "danger");
            } else {
              uipNotification(data.message, "success");
              self.masterSearch();
              self.contextual.open = false;
            }
          }
        },
      });
    },
    handleQuickAaction(index) {
      let self = this;
      let item = self.search.categorized[index];
      if (item.actionType == "link") {
        document.getElementById("uip-quick-link-" + index).click();
      }
      if (item.actionType == "function") {
        if (item.function == "darkmode") {
          self.uip_save_preferences("darkmode", true, false);
          jQuery("html").attr("data-theme", "dark");
        }
        if (item.function == "lightmode") {
          self.uip_save_preferences("darkmode", false, false);
          jQuery("html").attr("data-theme", "light");
        }
      }
    },
    handleMenuAaction(index) {
      let self = this;
      let item = self.search.categorized[index];
      document.getElementById("uip-menu-link-" + index).click();
    },
    handleContextual(index) {
      let self = this;
      let item = self.search.categorized[index];
      self.setContextual(item);
      self.contextual.open = true;
    },
    uip_save_preferences(pref, value, notification) {
      if (pref == "") {
        return;
      }

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_save_user_prefs",
          security: uip_ajax.security,
          pref: pref,
          value: value,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            if (!data.error) {
              if (notification != true) {
                uipNotification(data.message, "success");
              }
            }
          }
        },
      });
    },
    setContextual(item) {
      let self = this;
      self.search.searching = true;
      self.contextual.item = item;

      //mac or windows
      let mac = false;
      if (navigator.userAgent.indexOf("Mac OS X") != -1) {
        mac = true;
      }
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_get_contextual",
          security: uip_ajax.security,
          item: item,
          mac: mac,
        },
        success: function (response) {
          try {
            data = JSON.parse(response);
          } catch (error) {
            self.setContextual(item);
            return;
          }
          data = JSON.parse(response);
          if (data.error) {
            uipNotification(data.error_message, "danger");
          } else {
            self.contextual.actions = data.actions;
            self.contextual.open = true;
            self.search.activeIndex = 0;
          }
          self.search.searching = false;
        },
      });
    },
    openThisComponent() {
      let self = this;
      self.getQuickActions();
      self.search.open = true;
      self.$nextTick(() => {
        self.$refs.uipsearchsite.focus();
      });
      document.documentElement.addEventListener("click", this.onClickOutside, false);
      self.search.activeIndex = 0;
    },
    onClickOutside(event) {
      let self = this;
      if (event.target.classList.contains("uip-search-closer")) {
        self.search.open = false;
      }
    },
    getQuickActions() {
      let self = this;
      if (self.fetchQuickAactions) {
        return;
      }
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_get_quick_actions",
          security: uip_ajax.security,
        },
        success: function (response) {
          data = JSON.parse(response);
          if (data.error) {
            uipNotification(data.error_message, "danger");
          } else {
            self.search.categorized = data.actions;
            self.quickActions = data.actions;
            self.fetchQuickAactions = true;
            self.search.activeIndex = 0;
          }
        },
      });
    },
    masterSearch() {
      if (this.search.searching) {
        return;
      }
      let self = this;
      self.search.searching = true;

      let searchString = this.search.term;

      if (searchString == "") {
        self.search.categorized = self.quickActions;
        self.search.searching = false;
        self.search.nothingFound = false;
        return;
      }

      searchString = searchString.toLowerCase();

      perpage = this.search.perPage;
      currentpage = this.search.currentPage;
      this.search.loading = true;
      this.search.nothingFound = false;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_site_global_search",
          security: uip_ajax.security,
          search: searchString,
          perpage: perpage,
          currentpage: currentpage,
        },
        success: function (response) {
          self.search.loading = false;
          self.search.searching = false;
          if (response) {
            data = JSON.parse(response);
            if (data.error) {
              uipNotification(data.error_message, "danger");
            } else {
              self.search.results = data.founditems;
              self.search.totalPages = data.totalpages;
              self.search.totalFound = data.totalfound;
              self.search.categorized = data.categorized;
              self.search.activeIndex = 0;

              if (self.search.categorized.length < 1) {
                self.search.nothingFound = true;
                return;
              }

              if (self.search.currentPage > data.totalpages) {
                self.search.currentPage = 1;
                //self.masterSearch();
              }
            }
          }
        },
      });
    },
    loadMoreResults() {
      perpage = this.search.perPage;
      this.search.perPage = Math.floor(perpage * 3);
      this.masterSearch();
    },
    openSearch() {
      if (document.activeElement) {
        document.activeElement.blur();
      }
      this.search.open = true;
    },
    closeSearch() {
      if (document.activeElement) {
        document.activeElement.blur();
      }
      this.search.open = false;
    },
    isEnabled() {
      search = this.options.toolbar.options["search-disabled"].value;

      if (search == "true" || search === true) {
        return false;
      }

      return true;
    },
    setTarget(item) {
      if (item.newtab) {
        return "_blank";
      } else {
        return "_self";
      }
    },
  },
  template:
    '<div v-show="search.open" class="uip-position-fixed uip-left-0 uip-right-0 uip-top-0 uip-bottom-0 uip-flex uip-flex-center uip-flex-middle uip-search-closer uip-body-font uip-z-index-9999" ref="dropouter" style="font-size:13px;line-height:1">\
      <div class="uip-shadow-large uip-h-420 uip-max-h-100p uip-w-600 uip-max-w-100p uip-background-default  uip-border-round" ref="dropinner uip-overflow-visible">\
        <div class="uip-flex uip-flex-column uip-h-100p uip-max-h-100p">\
          <!-- SEARCH -->\
          <div class="">\
            <div class="uip-margin-bottom- uip-padding-s uip-border-bottom ">\
              <div class="uip-flex uip-flex-center">\
                <span class="uip-margin-right-xs uip-text-muted">\
                  <span class="material-icons-outlined">search</span>\
                </span> \
                <input type="text" :placeholder="translations.search" class="uip-blank-input uip-flex-grow" \
                @change="masterSearch()" style="color: var(--uip-text-color-normal)"\
                v-model="search.term" ref="uipsearchsite">\
                <div class="uip-flex uip-flex-center uip-gap-xxxs uip-text-muted">\
                  <span style="font-size:1em" v-if="os == \'mac\'" class="uip-border-round uip-border uip-padding-left-xxs uip-padding-right-xxs uip-background-muted">cmd</span>\
                  <span style="font-size:1em" v-if="os == \'pc\'" class="uip-border-round uip-border uip-padding-left-xxs uip-padding-right-xxs uip-background-muted">ctrl</span>\
                  <span style="font-size:1em" class="uip-border-round uip-border uip-padding-left-xxs uip-padding-right-xxs uip-background-muted uip-margin-right-xxs">d</span>\
                  <span>{{translations.forceSearchDirectory}}</span>\
                </div>\
              </div>\
            </div>\
          </div>\
          <div class="uip-overflow-auto uip-scrollbar" style="flex: 1">\
            <div class=" uip-max-h-100p">\
              <div class="uip-h-2 uip-w-100p">\
                <div class="uip-loading-box" v-if="search.searching">\
                  <div class="uip-loader"></div>\
                </div>\
              </div>\
              <!-- SEARCH RESULTS -->\
              <div class="uip-padding-xs" v-if="!contextual.open">\
                <div v-if="search.nothingFound" class="uip-flex uip-flex-middle uip-flex-center uip-h-150 uip-flex-column uip-row-gap-s">\
                  <div class="uip-text-muted">{{translations.searchDirectory}}</div>\
                  <button class="uip-button-default uip-flex uip-gap-xxs" @click="searchDirectory()">\
                    <span>Search</span> \
                    <span class="material-icons-outlined uip-border-round uip-border uip-padding-left-xxs uip-padding-right-xxs" style="font-size:1em">keyboard_return</span>\
                  </button>\
                </div>\
                <template v-for="(item, index) in search.categorized" >\
                  <div v-if="item.type == \'quickAction\'"  @mouseover="search.activeIndex = index" :class="{\'uip-background-muted\' : index == search.activeIndex }"\
                  :id="\'found-item-\' + index"\
                  class="uip-padding-xs uip-border-round uip-cursor-pointer">\
                    <div @click="handleQuickAaction(index)" class="uip-flex uip-gap-xs uip-flex-center uip-text-normal">\
                      <div class="material-icons-outlined" v-html="item.icon"></div>\
                      <div class=" uip-text-emphasis" v-html="item.title"></div>\
                      <div class="material-icons-outlined uip-flex-grow uip-text-right">bolt</div>\
                    </div>\
                    <a v-if="item.actionType == \'link\'" :href="item.link" :id="\'uip-quick-link-\' + index"></a>\
                  </div>\
                  <div v-else-if="item.type == \'menu\'" @mouseover="search.activeIndex = index" :class="{\'uip-background-muted\' : index == search.activeIndex }"\
                  :id="\'found-item-\' + index" @click="handleMenuAaction(index)"\
                  class="uip-padding-xs uip-border-round uip-cursor-pointer">\
                    <div class="uip-flex uip-gap-xs uip-flex-center uip-text-normal">\
                      <div class="material-icons-outlined">menu</div>\
                      <div class="uip-flex uip-gap-xxs uip-flex-center">\
                        <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-text-s uip-text-capitalize " v-html="item.type"></div>\
                        <div class="material-icons-outlined">chevron_right</div>\
                        <div v-if="item.parentItem" class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-text-s" v-html="item.parentItem"></div>\
                        <div v-if="item.parentItem" class="material-icons-outlined">chevron_right</div>\
                        <div class="uip-text-bold uip-text-emphasis" v-html="item.name"></div>\
                      </div>\
                    </div>\
                    <a :href="item.url" :id="\'uip-menu-link-\' + index"></a>\
                  </div>\
                  <div v-else-if="item.type == \'plugin\'" @mouseover="search.activeIndex = index" :class="{\'uip-background-muted\' : index == search.activeIndex }"\
                  :id="\'found-item-\' + index" @click="handleContextual(index)"\
                  class="uip-padding-xs uip-border-round uip-cursor-pointer">\
                    <div class="uip-flex uip-gap-xs uip-flex-center uip-text-normal">\
                      <div class="material-icons-outlined">extension</div>\
                      <div class="uip-flex uip-gap-xxs uip-flex-center">\
                        <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-text-s">{{item.type}}</div>\
                        <div class="material-icons-outlined">chevron_right</div>\
                        <div class="uip-text-bold uip-text-emphasis" v-html="item.Name"></div>\
                      </div>\
                    </div>\
                  </div>\
                  <div v-else @mouseover="search.activeIndex = index" :class="{\'uip-background-muted\' : index == search.activeIndex }"\
                  :id="\'found-item-\' + index" @click="handleContextual(index)"\
                  class="uip-padding-xs uip-border-round uip-cursor-pointer">\
                    <div class="uip-flex uip-gap-xs uip-flex-center uip-text-normal">\
                      <div class="material-icons-outlined">save</div>\
                      <div class="uip-flex uip-gap-xxs uip-flex-center">\
                        <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-padding-right-xxs uip-text-bold uip-text-s">{{item.type}}</div>\
                        <div class="material-icons-outlined">chevron_right</div>\
                        <div class="uip-text-bold uip-text-emphasis" v-html="item.name"></div>\
                      </div>\
                    </div>\
                  </div>\
                </template>\
              </div>\
              <!-- CONTEXTUAL RESULTS -->\
              <div class="uip-padding-xs" v-if="contextual.open">\
                <!--META -->\
                <div class="uip-flex uip-gap-xs uip-flex-center uip-margin-bottom-s">\
                  <div class="material-icons-outlined uip-cursor-pointer" style="font-size:2.5em" @click="contextual.open = false">chevron_left</div>\
                  <div class="" v-if="contextual.item.image">\
                    <img :src="contextual.item.image" class="uip-border-round uip-w-38">\
                  </div>\
                  <div class="">\
                    <div class="uip-text-bold uip-text-emphasis uip-margin-bottom-xxs" v-html="contextual.item.name"></div>\
                    <div class="uip-text-muted" v-html="contextual.item.author"></div>\
                  </div>\
                </div>\
                <!--DESCRIPTION-->\
                <div v-if="contextual.item.shortDes" class="uip-text-muted uip-padding-xs uip-margin-bottom-s">{{contextual.item.shortDes}}</div>\
                <!--ACTIONS-->\
                <template v-for="(item, index) in contextual.actions" >\
                  <div @mouseover="search.activeIndex = index" :class="{\'uip-background-muted\' : index == search.activeIndex }" @click="handleContextualAction(index)"\
                  :id="\'found-item-\' + index"\
                  class="uip-padding-xs uip-border-round uip-cursor-pointer">\
                    <div class="uip-flex uip-gap-xs uip-flex-center uip-text-normal">\
                      <div class="material-icons-outlined" v-html="item.icon"></div>\
                      <div class="" v-html="item.title"></div>\
                    </div>\
                    <a v-if="item.type == \'link\'" :target="setTarget(item)" :href="item.url" :id="\'uip-contextual-link-\' + index"></a>\
                  </div>\
                </template>\
              </div>\
            </div>\
          </div>\
          <!-- LOAD MORE -->\
          <div class="uip-border-top uip-padding-xs uip-padding-left-s uip-flex uip-gap-s uip-flex-center uip-text-muted">\
            <div class="uip-flex uip-flex-center uip-gap-xs">\
              <span style="font-size:1em" class="material-icons-outlined uip-border-round uip-border uip-padding-left-xxs uip-padding-right-xxs uip-background-muted">swap_vert</span>\
              <span>{{translations.toNavigate}}</span>\
            </div>\
            <div class="uip-flex uip-flex-center uip-gap-xs">\
              <span style="font-size:1em" class="material-icons-outlined uip-border-round uip-border uip-padding-left-xxs uip-padding-right-xxs uip-background-muted">keyboard_return</span>\
              <span>{{translations.toSelect}}</span>\
            </div>\
            <div class="uip-flex uip-flex-center uip-gap-xs">\
              <span style="font-size:1em" class="uip-border-round uip-border uip-padding-left-xxs uip-padding-right-xxs uip-background-muted">esc</span>\
              <span>{{translations.toClose}}</span>\
            </div>\
            <div class="uip-text-muted uip-text-s uip-flex-grow uip-text-right" v-if="search.totalPages > 1" >\
              <span>{{search.totalFound}}</span>\
              <span>{{translations.totalFound}}</span>\
            </div>\
          </div>\
        </div>\
      </div>\
    </div>',
});

/////////////////////////
//ADDS FEATURE TAG///////
/////////////////////////
uipCommand.component("feature-flag", {
  props: {
    translations: Object,
  },
  data: function () {
    return {
      loading: true,
    };
  },
  mounted: function () {},
  methods: {},
  template:
    '<span class="uip-padding-xxs uip-border-round uip-background-orange uip-text-bold uip-text-white uip-flex">\
	  <span class="material-icons-outlined uip-margin-right-xs">\
	  	card_giftcard\
	  </span>\
  	  <span>\
		{{translations.preFeature}}\
	  </span>\
  	</span>',
});

/////////////////////////
//LOADING PLACEHOLDER///////
/////////////////////////
uipCommand.component("loading-placeholder", {
  props: {
    settings: Object,
  },
  data: function () {
    return {
      loading: true,
    };
  },
  mounted: function () {
    this.loading = false;
  },
  methods: {},
  template:
    '<svg role="img" width="400" height="200" style="width:100%" aria-labelledby="loading-aria" viewBox="0 0 400 200" preserveAspectRatio="none">\
      <title id="loading-aria">Loading...</title>\
      <rect x="0" y="0" width="100%" height="100%" clip-path="url(#clip-path)" style=\'fill: url("#fill");\'></rect>\
      <defs>\
        <clipPath id="clip-path">\
          <rect x="0" y="18" rx="2" ry="2" width="211" height="16" />\
          <rect x="0" y="47" rx="2" ry="2" width="120" height="16" />\
          <rect x="279" y="47" rx="2" ry="2" width="120" height="16" />\
          <rect x="0" y="94" rx="2" ry="2" width="211" height="16" />\
          <rect x="0" y="123" rx="2" ry="2" width="120" height="16" />\
          <rect x="279" y="123" rx="2" ry="2" width="120" height="16" />\
          <rect x="0" y="173" rx="2" ry="2" width="211" height="16" />\
          <rect x="0" y="202" rx="2" ry="2" width="120" height="16" />\
          <rect x="279" y="202" rx="2" ry="2" width="120" height="16" />\
        </clipPath>\
        <linearGradient id="fill">\
          <stop offset="0.599964" stop-color="#bbbbbb2e" stop-opacity="1">\
            <animate attributeName="offset" values="-2; -2; 1" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
          </stop>\
          <stop offset="1.59996" stop-color="#bbbbbb2e" stop-opacity="1">\
            <animate attributeName="offset" values="-1; -1; 2" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
          </stop>\
          <stop offset="2.59996" stop-color="#bbbbbb2e" stop-opacity="1">\
            <animate attributeName="offset" values="0; 0; 3" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
          </stop>\
        </linearGradient>\
      </defs>\
  </svg>',
});

uipCommand.mount("#uip-command-center");
