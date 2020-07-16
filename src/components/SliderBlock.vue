<template>

  <div
    class="k-editor-slider-block"
    tabindex="0"
    ref="element"
    @keydown.delete.self="$emit('remove')"
    @keydown.enter.self="$emit('convert', 'paragraph')"
    @keydown.up="$emit('prev')"
    @keydown.down="$emit('next')"
  >
    <k-dropzone @drop="(files) => onDrop(files)">
      <div class="k-editor-slider-block-images"
      @keydown.delete.prevent.self="$emit('remove')"
      >
        <!-- Empty Case -->
        <div v-if="images.length === 0" class="k-editor-slider-block-images-empty">
          <button class="k-button" @keydown.enter.native.stop @click.stop.prevent="uploadFiles">
            <k-icon type="upload"></k-icon>
            <span class="k-button-text">{{$t('editor.blocks.slider.upload')}}</span>
          </button>
          <span class="separator">{{$t('editor.blocks.slider.or')}}</span>
          <button class="k-button" @keydown.enter.native.stop @click.stop.prevent="selectFiles">
            <k-icon type="image"></k-icon>
            <span class="k-button-text">{{$t('editor.blocks.slider.select')}}</span>
          </button>
        </div>

        <k-draggable
          :options="{ group: attrs.group }"
          :list="images"
          class="k-editor-slider-block-images-draggable"
          :class="{ 'empty-draggable': images.length === 0 }"
          @end="onDragEnd"
        >
          <div
            v-for="(image, imageIndex) in images"
            :key="image.guid"
            :style="calcImageDimensions(image)"
            class="k-editor-image k-editor-slider-block-image"
            @keydown.delete.prevent.self="deleteImage(imageIndex)"
            @keydown.left.self="focusImage(imageIndex - 1)"
            @keydown.right.self="focusImage(imageIndex + 1)"
            tabindex="0"
          >
            <button class="k-button" @click.prevent.stop="openImageSetting(image)">
              <k-icon type="cog"></k-icon>
            </button>
            <img
              @dblclick.prevent.stop="editImage(image)"
              @dragstart.prevent
              v-once
              @error="imageLoadError($event)"
              :data-src="image.src"
              alt
            />
          </div>
        </k-draggable>
      </div>
    </k-dropzone>

    <div v-if="this.images.length">
      <figcaption>
        <k-editable
          :content="attrs.caption"
          :breaks="true"
          :placeholder="$t('editor.blocks.slider.caption.placeholder') + '…'"
          :spellcheck="spellcheck"
          @prev="focus"
          @shiftTab="focus"
          @tab="$emit('next', $event)"
          @next="$emit('next', $event)"
          @split="$emit('append')"
          @enter="$emit('append')"
          @keydown.delete.prevent.self="$emit('remove')"
          @input="caption"
        />
      </figcaption>
    </div>


        <!-- Files Selector Dialog -->
    <k-files-dialog ref="fileDialog" @submit="insertFiles($event)" />
    <!-- Upload File Dialog -->
    <k-upload ref="fileUpload" @success="insertUpload" />
    <!-- Settings Dialog -->
    <k-dialog ref="settings" @submit="saveSettings" size="medium">
      <k-form :fields="fields" v-model="attrs" @submit="saveSettings" />
    </k-dialog>
    <!-- Image Settings Dialog -->
    <k-dialog ref="imageSettings" @submit="saveImageSettings" size="medium">
      <k-form :fields="imageFields" v-model="imageSettingsImage" @submit="saveImageSettings" />
    </k-dialog>
  </div>

</template>

<script>
import lozad from "lozad";
const sortAlphaNum = (a, b) =>
  a.id.localeCompare(b.id, "en", { numeric: true });

export default {
  icon: "image",
  label: "Slider",
  props: {
    attrs: {
      type: Object
    },
    endpoints: Object,
    spellcheck: Boolean
  },
  data: () => ({
    images: [],
    imageSettingsImage: null
  }),
  created() {
    if (!this.attrs.images) {
      this.input({
        ...this.attrs,
        // default values
        lazyLoading: 1,
        loop: 0,
        autoplay: 0,
        duration: 2000,
        slidesPerView: 1,
        arrows: 0,
        dots: 1,
        zoom: 0,
        images: [],
      });
    } else {
      this.images = this.attrs.images;
    }
  },
  watch: {
    attrs: function(val) {
      this.images = val.images;
    }
  },
  computed: {
    style() {
      if (this.attrs.ratio) {
        return "padding-bottom:" + 100 / this.attrs.ratio + "%";
      }
    },
    fields() {
      return {
        css: {
          label: this.$t('editor.blocks.slider.css.label'),
          type: "text",
          icon: "code",
        },
        lazyLoading: {
          label: this.$t('editor.blocks.slider.lazyLoading.label'),
          type: "toggle",
        },
        loop: {
          label: this.$t('editor.blocks.slider.loop.label'),
          type: "toggle",
        },
        autoplay: {
          label: this.$t('editor.blocks.slider.autoplay.label'),
          type: "toggle",
        },
        duration: {
          label: this.$t('editor.blocks.slider.duration.label'),
          type: "number",
        },
        slidesPerView: {
          label: this.$t('editor.blocks.slider.slidesPerView.label'),
          type: "number",
        },
        arrows: {
          label: this.$t('editor.blocks.slider.arrows.label'),
          type: "toggle",
        },
        dots: {
          label: this.$t('editor.blocks.slider.dots.label'),
          type: "toggle",
        },
        zoom: {
          label: this.$t('editor.blocks.slider.zoom.label'),
          type: "toggle",
        },

      };
    },
    imageFields() {
      return {
        altText: {
          label: this.$t("editor.blocks.slider.imageSettings.altText"),
          type: "text",
          icon: "text"
        },
        imageClass: {
          label: this.$t("editor.blocks.slider.imageSettings.imageClass"),
          type: "text",
          icon: "cog"
        }
      };
    }
  },
  mounted() {
    this.lazyLoadImages();
  },
  updated() {
    this.lazyLoadImages();
  },
  methods: {
    async focus(options = {}) {
      await this.$nextTick();
      const element = this.$refs.element;
      if (options.focusRoot === true || this.images.length === 0) {
        this.$refs.element.focus();
        return;
      }
      const images = Array.from(
        element.querySelectorAll(".k-editor-slider-block-image")
      );
      images[0].focus();
    },
    async focusImage(imageIndex) {
      await this.$nextTick();
      const element = this.$refs.element;
      const images = Array.from(
        element.querySelectorAll(".k-editor-slider-block-image")
      );
      if (images.length === 0) {
        this.focus({ focusRoot: true });
        return;
      }
      if (imageIndex < 0) {
        images[0].focus();
        return;
      }
      if (imageIndex >= images.length) {
        images[images.length - 1].focus();
        return;
      }
      images[imageIndex].focus();
    },
    calcImageDimensions(image) {
      const imageHeight = 100;
      const imageRatio = image.width / image.height;
      const imageWidth = imageHeight * imageRatio;
      return `position: relative; height: 0; width: ${imageWidth}px; padding-bottom: ${imageHeight}px;`;
    },
    lazyLoadImages() {
      const observer = lozad(".k-editor-slider-block-image img");
      observer.observe();
    },
    imageLoadError(event) {
      // Sometimes the service might not render the thumb in time
      // and returns a 500 error for getting the image
      // especially when a lot of images were uploaded, so retry fetching after a while
      const img = event.currentTarget;
      if (!img) {
        return;
      }
      const src = img.getAttribute("src");
      const retries = parseInt(img.getAttribute("retries")) || 0;
      if (retries < 5) {
        setTimeout(() => {
          img.setAttribute("src", src);
          img.setAttribute("retries", retries + 1);
        }, 200);
      }
    },
    input(data) {
      this.$emit("input", {
        attrs: {
          ...this.attrs,
          ...data
        }
      });
      this.lazyLoadImages();
    },
    async fetchFile(link) {
      const response = await this.$api.get(link);
      return {
        guid: response.link,
        src: response.url,
        id: response.id,
        filename: response.filename,
        ratio: response.dimensions.ratio,
        width: response.dimensions.width,
        height: response.dimensions.height
      };
    },
    onDragEnd() {
      const images = this.images;
      this.input({
        images
      });
    },
    async insertUpload(files, responses) {
      const uploads = await Promise.all(
        responses.map(response => this.fetchFile(response.link))
      );
      const images = this.images;
      const newImageList = [...images, ...uploads];

      this.input({
        images: newImageList
      });

      this.$events.$emit("file.create");
      this.$events.$emit("model.update");
      this.$store.dispatch("notification/success", ":)");
    },
    caption(html) {
      this.input({
        caption: html
      });
    },
    editImage(image) {
      if (image.guid) {
        this.$router.push(image.guid);
      }
    },
    menu() {
      // If no images array, or settings ref has not rendered,
      // don't return menu items
      if (!this.images || !this.$refs.settings) {
        return [];
      }
      return [
        {
          icon: "cog",
          label: this.$t("editor.blocks.slider.settings.label"),
          click: this.$refs.settings.open
        },
        {
          icon: "image",
          label: this.$t("editor.blocks.slider.replace.label"),
          click: this.replace
        },
        {
          icon: "add",
          label: this.$t("editor.blocks.slider.add.label"),
          click: this.selectFiles
        },
      ];
    },
    onDrop(files) {
      this.$refs.fileUpload.drop(files, {
        url: window.panel.api + "/" + this.endpoints.field + "/upload",
        multiple: true,
        accept: "image/*"
      });
    },
    uploadFiles() {
      this.$refs.fileUpload.open({
        url: window.panel.api + "/" + this.endpoints.field + "/upload",
        multiple: true,
        accept: "image/*"
      });
    },
    replace() {
      this.input({
        images: []
      })
    },
    selectFiles() {
      this.$refs.fileDialog.open({
        endpoint: this.endpoints.field + "/files",
        multiple: true,
        selected: []
      });
    },
    async insertFiles(files) {
      const objects = await Promise.all(
        files.map(file => this.fetchFile(file.link))
      );
      const images = this.images;
      const newImages = [...images, ...objects];
      this.input({
        images: newImages
      });
    },
    deleteImage(imageIndex) {
      const images = this.images;
      const newImages = [
        ...images.slice(0, imageIndex),
        ...images.slice(imageIndex + 1, images.length)
      ];
      this.input({
        images: newImages
      });
      if (newImages.length === 0) {
        this.focus({ focusRoot: true });
      } else {
        const focusIndex = imageIndex > 0 ? imageIndex - 1 : 0;
        this.focusImage(focusIndex);
      }
    },
    settings() {
      this.$refs.settings.open();
    },
    saveSettings() {
      this.$refs.settings.close();
      this.input(this.attrs);
    },
    openImageSetting(image) {
      this.imageSettingsImage = image;
      this.$refs.imageSettings.open();
    },
    clearImageSettingImage() {
      this.imageSettingsImage = null;
    },
    saveImageSettings() {
      this.clearImageSettingImage();
      this.$refs.imageSettings.close();
      this.input(this.attrs);
    }
  }
};
</script>

<style lang="scss">
@import "../variables.scss";

.k-editor-slider-block {
  position: relative;
  user-select: none;
  &:focus {
    outline: 0;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.5);
  }
  .k-editor-slider-block-images {
    position: relative;
    .k-editor-slider-block-images-empty {
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      right: 0;
      display: flex;
      justify-content: center;
      align-content: center;
      background-color: rgba($color: #000000, $alpha: 0.1);
      .separator {
        font-style: italic;
        color: #888;
        font-size: $font-size-small;
        margin-left: 1rem;
        margin-right: 1rem;
      }
      button {
        span {
          margin-left: 0.1rem;
        }
      }
      span {
        display: inline-flex;
        align-items: center;
      }
    }
    .k-draggable {
      display: flex;
      flex-wrap: wrap;
      &.empty-draggable {
        padding: 20px;
      }
      .k-editor-slider-block-image {
        position: relative;
        button {
          position: absolute;
          display: none;
          top: 10px;
          right: 10px;
          border-radius: 2px;
          padding: 2px;
          background: $color-background;
          z-index: 10;
        }
        &:hover button {
          display: block;
        }
        img {
          display: block;
          position: absolute;
          height: 100%;
          width: 100%;
          padding: 1px;
          top: 0;
          left: 0;
        }
        &.ghost {
          opacity: 0;
        }
      }
    }
  }

  figcaption {
    display: block;
    margin-top: .75rem;
  }
}

.k-editor-slider-block-image.sortable-chosen.sortable-drag {
  position: relative;
  img {
    width: 100%;
    opacity: 0.5;
  }
}
.k-editor-slider-block-image.sortable-chosen.k-sortable-ghost {
  img {
    object-fit: contain;
  }
}
.k-editor-slider-block .k-editable-placeholder,
.k-editor-slider-block .ProseMirror {
  text-align: center;
  font-size: 0.875rem;
  line-height: 1.5em;
}
</style>
