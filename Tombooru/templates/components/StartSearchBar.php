<form action="<?= URL::getURL('/posts') ?>" data-tombooru-component="SidebarSearchBar">
  <div class="search-bar">
    <div class="search-input">
      <input
        type="search"
        name="search"
        placeholder="Type to search"
        aria-label="Search posts by tag"
        placeholder="Search posts by tag"
        autocapitalize="none"
        autocomplete="off"
      />
      <div class="button-container">
        <button class="button" type="submit" name="tbgo" title="Submit to search posts">Go</button>
      </div>
      <div class="autocomplete-container">
      </div>
    </div>

  </div>
  <script>Tombooru.decorateComponent()</script>
</form>
