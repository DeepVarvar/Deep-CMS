


var fm = {


    params: {

        winWidth   : 620,
        winHeight  : 450,
        language   : "en",
        targetName : null,
        targetObj  : null

    },

    bind: function(params) {

        if (!params.targetObj || !params.targetName || !params.language || !params.fmUrl) {
            return;
        }

        this.params.targetName = params.targetName;
        this.params.targetObj  = params.targetObj;
        this.params.fmUrl      = params.fmUrl;
        this.params.language   = params.lang;

        this.params.targetObj.config['filebrowserBrowseUrl']    = this.params.fmUrl;
        this.params.targetObj.config['filebrowserWindowWidth']  = this.params.wWidth;
        this.params.targetObj.config['filebrowserWindowHeight'] = this.params.wHeight;

    }


}



