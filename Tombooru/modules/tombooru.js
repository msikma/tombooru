// Note: this file is loaded in the <head> section.

class APIClient {
  constructor(baseURL = '') {
    // Find the API base URL pathname.
    const {origin, pathname} = window.location;
    const base = pathname.slice(0, pathname.indexOf('/imageboard'))
    this.baseURL = `${origin}${base}${baseURL}`;
  }

  async get(endpoint, params = {}, headers = {}) {
    return this._request(this._buildUrl(endpoint, params), 'GET', null, headers);
  }

  async post(endpoint, params = {}, data = {}, headers = {}) {
    return this._request(this._buildUrl(endpoint, params), 'POST', data, headers);
  }

  async _request(endpoint, method, data = null, headers = {}) {
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        ...headers
      }
    };

    if (data && method !== 'GET') {
      options.body = JSON.stringify(data);
    }

    const response = await fetch(endpoint, options);
    if (!response.ok) {
      throw new Error(`Status: ${response.status}`);
    }

    return response.json();
  }

  _buildUrl(endpoint, params = {}) {
    const url = new URL(this.baseURL + endpoint);
    const urlParams = new URLSearchParams(Object.entries(params))
    url.search = urlParams.toString();
    return url.toString();
  }
}

class Tombooru {
  static activeComponents = [];
  static api = new APIClient('/imageboard/api');
  /**
   * Returns all defined component classes.
   */
  static getComponents() {
    const components = Object.entries(this).map(([key, value]) => {
      if (!key.startsWith('Component') || typeof value !== 'function' || !value.prototype) {
        return null;
      }
      return [key, value];
    });
    return Object.fromEntries(components.filter(c => c));
  }
  /**
   * Decorates an element with component logic.
   */
  static decorateComponent() {
    const script = document.currentScript;
    const el = script.parentElement;
    if (!el) {
      return;
    }
    const name = el.getAttribute('data-tombooru-component');
    if (!name) {
      return;
    }
    const components = this.getComponents();
    const componentName = `Component${name}`;
    const ComponentClass = components[componentName];
    if (!ComponentClass) {
      return;
    }
    this.activeComponents.push(new ComponentClass(el, this));
  }
  /**
   * Sets a cookie.
   */
  static setCookie(name, value, days = 4383) {
    const cookieName = `tombooru-${name}`;
    let cookie = `${cookieName}=${encodeURIComponent(value)}; path=/; SameSite=Lax`;
    if (days !== null) {
      const date = new Date();
      date.setTime(date.getTime() + (days * 86400000));
      cookie += `; expires=${date.toUTCString()}`;
    }
    document.cookie = cookie;
  }
  /**
   * Gets a cookie.
   */
  static getCookie(name) {
    const cookieName = `tombooru-${name}`;
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${cookieName}=`);
    if (parts.length === 2) {
      return decodeURIComponent(parts.pop().split(';').shift());
    }
    return null;
  }
}

Tombooru.ComponentPostFormSubmit = class {
  constructor(el) {
    this.el = el;
    this.decorate();
  }
  decorate() {
    this.form = this.el.closest('form');
    this.form.addEventListener('submit', () => {
      this.el.disabled = true;
    });
  }
}
Tombooru.ComponentUserUpvoteDownvote = class {
  constructor(el, base) {
    this.el = el;
    this.scoreEls = [...el.querySelectorAll('.score')];

    const vote = Number(el.getAttribute('data-vote'));
    const score = Number(el.getAttribute('data-score'));
    const postID = Number(el.getAttribute('data-post-id'));

    this.state = {
      // The score when the page got loaded. Updated by postVoteStatus().
      baseScore: score - vote,
      // Our vote.
      vote,
      // The currently displayed score.
      score,
      // Post ID to save updates to.
      postID,
      // Whether to add the "active" state to the arrows when voted.
      useActiveState: true,
    };

    this.setVoteState();
    this.api = base.api;
    this.decorate();
  }

  setVoteState() {
    const value = this.state.vote;
    this.el.classList.remove('is-upvoted');
    this.el.classList.remove('is-downvoted');
    if (value === 1) {
      this.el.classList.add('is-upvoted');
    }
    else if (value === -1) {
      this.el.classList.add('is-downvoted');
    }
    if (this.state.useActiveState) {
      [...this.el.querySelectorAll('.set-upvote > a')].forEach(a => a.classList.toggle('active', value === 1));
      [...this.el.querySelectorAll('.set-downvote > a')].forEach(a => a.classList.toggle('active', value === -1));
    }
  }

  async changeVote(value) {
    this.state.vote = value;
    this.setVoteState();
    this.state.score = this.state.baseScore + value;
    this.updateScore();
    await this.postVoteStatus(value)
  }

  async postVoteStatus(value) {
    const postID = this.state.postID;
    const res = await this.api.get('/post_ranking/vote', {post_id: postID, vote: value});
    try {
      const interactions = res.data.postUserInteractions;
      const isUpvoted = interactions.upvote.state;
      const isDownvoted = interactions.downvote.state;
      const confirmedVote = !isUpvoted && !isDownvoted ? 0 : (isUpvoted ? 1 : -1);
      const confirmedScore = res.data.postRanking.ranking.score;
      this.state.vote = confirmedVote;
      this.state.baseScore = confirmedScore - confirmedVote;
      this.setVoteState();
      this.updateScore();
    }
    catch {
      console.error('could not update post upvote/downvote status %o %o', postID, value, res);
    }
  }

  updateScore() {
    this.scoreEls.forEach(el => el.innerText = this.state.baseScore + this.state.vote);
  }

  decorate() {
    [...this.el.querySelectorAll('a.icon')].forEach(btn => btn.addEventListener('click', ev => {
      ev.preventDefault()
      const intent = btn.classList.contains('upvote') ? 1 : -1;
      this.changeVote(this.state.vote === intent ? 0 : intent);
    }));
  }
}
Tombooru.ComponentNewPostDestinationFilename = class {
  constructor(el) {
    this.el = el;
    this.fnSource = document.querySelector('input[name="source_filename"]');
    this.fnDestination = el.querySelector('input[name="destination_filename"]');
    this.fnSuffix = el.querySelector('.suffix');
    this.decorate();
  }
  callback() {
    if (!this.fnSource.files?.length) {
      return;
    }
    const name = this.fnSource.files[0].name;
    const nameSegments = name.split('.');
    const extension = nameSegments.slice(-1)[0];
    const basename = nameSegments.slice(0, -1).join('.');
    const basenameCapitalized = basename.charAt(0).toUpperCase() + basename.slice(1);
    this.fnDestination.value = basenameCapitalized;
    this.fnDestination.placeholder = basenameCapitalized;
    this.fnSuffix.innerText = `.${extension}`;
  }
  decorate() {
    this.fnSource.addEventListener('change', () => this.callback());
  }
}
Tombooru.ComponentUserScalingSelector = class {
  constructor(el) {
    this.el = el;
    this.select = el.querySelector('select');
    this.decorate();
  }
  decorate() {
    this.el.addEventListener('change', (ev) => {
      const value = this.select.value;
      Tombooru.setCookie('user-scaling', value);
      const embed = document.querySelector('.tombooru-page .media-embed');
      embed.classList.remove('scaling-default');
      embed.classList.remove('scaling-fullwidth');
      embed.classList.add(`scaling-${value}`);
    });
  }
}
Tombooru.ComponentUserFavorite = class {
  constructor(el, base) {
    this.el = el;
    this.state = {
      isFaved: this.el.getAttribute('data-favorited') === '1',
      postID: parseInt(this.el.getAttribute('data-post-id'), 10),
    };
    this.api = base.api;
    this.decorate();
  }
  decorate() {
    this.el.addEventListener('click', async ev => {
      ev.preventDefault();
      const newStatus = !this.state.isFaved;
      this.state.isFaved = newStatus;
      this.setStatus();
      await this.postFavStatus(newStatus);
    })
  }
  async postFavStatus(status) {
    const postID = this.state.postID;
    const value = status ? 1 : 0;
    const res = await this.api.get('/post_ranking/fave', {post_id: postID, value});
    try {
      const confirmedStatus = res.data.postUserInteractions.favorite.state;
      this.state.isFaved = confirmedStatus;
      this.setStatus();
    }
    catch {
      console.error('could not update post favorite status %o %o', postID, status, res);
    }
  }
  setStatus() {
    const value = this.state.isFaved;
    this.el.classList.toggle('blue', !value);
    this.el.classList.toggle('pink', value);
    this.el.classList.toggle('active', value);
    this.el.classList.toggle('is-faved', value);
  }
}
Tombooru.ComponentPostEditTagsPreview = class {
  constructor(el) {
    this.el = el;
    this.input = el.querySelector('.group-input textarea')
    this.preview = el.querySelector('.input-preview')
    this.decorate();
  }
  callback() {
    const tags = this.input.value
      .split(/\s+/)
      .filter(Boolean);
    const buffer = [];
    if (!tags.length) {
      buffer.push(`<span class="item label">No tags entered.</span>`);
    }
    for (const tag of tags) {
      buffer.push(`<span class="item">${tag.replaceAll('_', ' ')}</span>`);
    }
    this.preview.innerHTML = `<div class="actions narrow">${buffer.join('')}</div>`;
  }
  decorate() {
    this.input.addEventListener('input', () => this.callback());
    this.callback();
  }
}
Tombooru.ComponentSidebarSearchBar = class {
  constructor(el, base) {
    this.el = el;
    this.searchInput = el.querySelector('.search-input');
    this.input = el.querySelector('input[type="search"]');
    this.submit = el.querySelector('button[type="submit"]');
    this.acContainer = el.querySelector('.autocomplete-container');
    this.debounceDuration = 150;
    this.decorate();
    this.api = base.api;
  }
  createAutocompleteHTML(data) {
    const {lastSearchTerm} = data;
    const searchTerm = lastSearchTerm.replaceAll('_', ' ')
    const buffer = [];
    buffer.push('<div class="autocomplete">');
    buffer.push('<table class="entries">');
    for (const item of data.suggestions) {
      buffer.push(`<tr class="tag-category" data-tag-category="${item.category}">`);
      const name = this.highlightSearchTerm(item.name.replaceAll('_', ' '), searchTerm);
      if (item.attributes.includes('isMoved')) {
        const oldName = this.highlightSearchTerm(item.oldName.replaceAll('_', ' '), searchTerm);
        buffer.push(`<td class="moved"><a href="#" class="term old">${oldName}</a><span class="arrow">→</span><a href="#" class="term new" data-term="${item.name}">${name}</a></td><td><span>${item.count}</span></td>`);
      }
      else {
        buffer.push(`<td><a href="#" class="term" data-term="${item.name}">${name}</a></td><td><span>${item.count}</span></td>`);
      }
      buffer.push(`</tr>`);
    }
    buffer.push('</table>');
    buffer.push('</div>');
    return buffer.join('\n');
  }
  highlightSearchTerm(name, searchTerm) {
    const matches = name.match(new RegExp(`(${searchTerm})(.*)`, 'i'))
    if (matches === null) {
      return name;
    }
    return `<b>${matches[1]}</b>${matches[2]}`;
  }
  async getSearchSuggestions(query) {
    const res = await this.api.get('/search_suggestions/get', {query});
    if (res.data.suggestions.length === 0) {
      this.removeSuggestions();
    }
    else {
      const html = this.createAutocompleteHTML(res.data);
      this.searchInput.classList.toggle('has-autocomplete', true);
      this.acContainer.innerHTML = html;
      this.bindSuggestions();
    }
  }
  bindSuggestions() {
    const terms = [...this.acContainer.querySelectorAll('a.term:not(.old)')];
    terms.forEach(term => term.addEventListener('click', ev => {
      ev.preventDefault();
      const termString = ev.target.getAttribute('data-term').trim();
      this.applySuggestion(termString);
    }))
  }
  applySuggestion(termString) {
    const reversedValue = [...this.input.value].reverse().join('');
    const reversedRemovedLastWord = reversedValue.replace(/^([\S]+)([\s\S]*)/, '$2')
    const removedLastWord = [...reversedRemovedLastWord].reverse().join('');
    const withTerm = [removedLastWord, termString].join('');
    this.input.value = withTerm;
    this.input.focus();
    this.removeSuggestions();
  }
  removeSuggestions() {
    const ac = this.acContainer.querySelector('.autocomplete');
    if (ac) {
      ac.remove();
    }
    this.searchInput.classList.toggle('has-autocomplete', false);
  }
  decorate() {
    let debounceTimeout = null;
    this.input.addEventListener('input', () => {
      if (this.input.value.trim() === '') {
        this.removeSuggestions();
      }
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => this.getSearchSuggestions(this.input.value), this.debounceDuration);
    });
    document.addEventListener('click', ev => {
      if (!this.input.contains(ev.target) && !this.acContainer.contains(ev.target)) {
        this.removeSuggestions();
      }
    });
  }
}
