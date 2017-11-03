Polymer({
  is: 'gv-detail-person',
  properties: {},
  attached() {
    GVCarto.ready(() => {
      gvc.initElementGlobals(this);
    });
    // Raw values.
    $.extend(this, this.data.properties);
    this.memberOf = this.data.memberOf;
    this.headOf = this.data.headOf;
    this.topicInterest = this.data.topicInterest;
    this.resourceNeeded = this.data.resourceNeeded;
    this.resourceProposed = this.data.resourceProposed;
    this.expertise = this.data.expertise;
    this.knows = this.data.knows;
    //log(this.data.building);
    this.buildingTitle = gvc.buildings[this.data.building].title;
    if (this.birthday) {
      let birthday = new Date(this.birthday);
      this.birthday = birthday.getDate() + '/' + (birthday.getMonth() + 1) + '/' + birthday.getFullYear();
    }
  },
    handleClickDetail(e) {
        e.preventDefault();
        gvc.goToPath('detail', {
            uri: window.encodeURIComponent(e.currentTarget.getAttribute('rel'))
        });
    },
    onClickThematic(e){
        e.preventDefault();
        let searchThemeFilter = document.getElementById('searchThemeFilter');
        searchThemeFilter.value = e.target.rel;
        //searchThemeFilter._activeChanged();
        gvc.goSearch();

    },
    handleClickRessource(e) {
        e.preventDefault();
        log('test');
        gvc.goToPath('ressource', {
            uri: window.encodeURIComponent(e.currentTarget.getAttribute('rel')),
            person: window.encodeURIComponent(this.uri)
        });
    },
});
