
var loggeduser = '';
//check for logged user at the beggining
window.onload = function()
{
    var currentkey = window.localStorage.getItem('loginkey');
    if (currentkey != null)
    {
        var data = { loginkey: currentkey };
        $.ajax({
                url: 'api/client/checklogged',
                type: 'POST',
                data: data
            }).then(function(data)
            {
                var result = JSON.parse(data);
                
                if (!result.success)
                    window.location.href = 'index.html';
                else
                {
                    loggeduser = result.user;
                    allowothers();
                }
            });
    }
    else
    {
        alert('Noone logged in');
        window.location.href = 'index.html';
    }
}

function allowothers()
{

//template for upper right dropdown account menu
Vue.component('account-menu',
{
    data: function () {
        return {
          items: [ {title: "Set Mailer"},{title: "Change Password"},{ title: "Logout" } ],
        }
      },
    template: `
    <div>
    <v-menu offset-y>
    <v-btn icon color="primary" dark slot="activator" large><v-icon large>account_circle</v-icon></v-btn>
        <v-list>
        <v-list-tile v-for="item in items" :key="item.title" @click="action(item.title)">
          <v-list-tile-title>{{ item.title }}</v-list-tile-title>
        </v-list-tile>
      </v-list>
    </v-menu>
    </div>
    `,
    methods:
    {
        action: function(value)
        {
            switch(value)
            {
                case 'Logout':
                    this.logout();
                    break;
                case 'Set Mailer':
                    layout.mailerdialog = true;
                    var data = { user: loggeduser};
                    $.ajax({
                        url: 'api/client/getmailer',
                        type: 'POST',
                        data: data
                    }).then(function(data)
                    { 
                        layout.mailer = data;
                    });
                    break;
                case 'Change Password':
                    layout.passdialog = true;
                    break;
            }
        },
        logout: function()
        {
            var data = {
                loginkey : window.localStorage.getItem('loginkey')
            };
        $.ajax({
            url: 'api/client/logout',
            type: 'POST',
            data: data
        }).then(function(data)
        {
            var result = JSON.parse(data);
            
            if (!result.success)
                alert("Logout not successful");
            else
            {
                window.localStorage.removeItem('loginkey');
                window.location.href = "index.html";
            }
        });
        }
    }
})

const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;

//upload files in new mail
Vue.component('file-upload',
{
    template: `<div id="upload">
    <div class="container" id="dropfiles">
    <!--UPLOAD-->
    <div v-if="isInitial || isSaving">
    <form enctype="multipart/form-data" novalidate >
      <b>Upload files</b>
      <div class="dropbox">
        <input type="file" multiple :name="uploadFieldName" :disabled="isSaving" @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length"
          accept="*" class="input-file">
          <p v-if="isInitial">
            Drag your file(s) here to begin<br> or click to browse
          </p>
          <p v-if="isSaving">
            Uploading {{ fileCount }} files...
          </p>
      </div>
    </form>
    </div>
    <!--SUCCESS-->
    <div v-if="isSuccess">
      <b>Uploaded {{ uploadedFiles.length }} file(s) successfully.</b>
      <p>
        <a href="javascript:void(0)" @click="reset()">Upload again</a>
      </p>
      <ul class="list-unstyled">
          <li v-for="item in uploadedFiles">
          {{item}}
          </li>
        </ul>
    </div>
    <!--FAILED-->
    <div v-if="isFailed">
      <b>Upload failed.</b>
      <p>
        <a href="javascript:void(0)" @click="reset()">Try again</a>
      </p>
      <pre>{{ uploadError }}</pre>
    </div>
  </div>
  </div>`,
  name: "upload",
    data() {
        return {
          uploadedFiles: [],
          uploadError: null,
          currentStatus: null,
          uploadFieldName: 'file',
          fileCount: 0,
          currenthash: ''
        }
      },
      computed: {
        isInitial() {
          return this.currentStatus === STATUS_INITIAL;
        },
        isSaving() {
          return this.currentStatus === STATUS_SAVING;
        },
        isSuccess() {
          return this.currentStatus === STATUS_SUCCESS;
        },
        isFailed() {
          return this.currentStatus === STATUS_FAILED;
        }
      },
      methods: {
        reset() {
            var data = { atts: this.uploadedFiles,
            user: loggeduser,
            hash: this.currenthash
         };
            $.ajax({
                url: 'api/emails/removeatts',
                data: data,
                type: 'POST',
              });

          this.currentStatus = STATUS_INITIAL;
          this.uploadedFiles = [];
          this.uploadError = null;
        this.currenthash = '';

          
        },
        save(formData) {

          this.currentStatus = STATUS_SAVING;

          $.ajax({
            url: 'api/emails/upload/' + loggeduser,
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
          }).then(data => {
              
            this.$nextTick(() =>{
                var result = JSON.parse(data);
                this.currenthash = result.hash;

                if (result.success)
                 {
                    this.currentStatus = STATUS_SUCCESS;
                    this.uploadedFiles = result.files;
                 }
                 else
                 {
                    this.currentStatus = STATUS_FAILED;
                    this.uploadError = "Files are too big to upload";
                 }
                }) 
          });
        },
        filesChange(fieldName, fileList) {
          formData = new FormData();
  
          if (!fileList.length) return;

          var i = 0;
          Array
            .from(Array(fileList.length).keys())
            .map(x => {
              formData.append(fieldName + i, fileList[x], fileList[x].name);
              i++;
            });

          this.save(formData);
        }
      },
      mounted() {
        this.reset();
      }
    }
)

//bottom right button for new email window
Vue.component('new-email',
{
    data: function () {
        return {
          to: '',
          subject: '',
          text: '',
          dialog: false,
          fwdinfo: null,
        }
      },
    template: `
    <div>
    <v-btn
      fab
      bottom
      right
      color="pink"
      dark
      fixed
      @click.stop="dialog = !dialog"
    >
    <v-icon>edit</v-icon>
    </v-btn> 
    <v-dialog v-model="dialog" width="800px">
      <v-card>
        <v-card-title
          class="grey lighten-4 py-4 title">
          Send email
        </v-card-title>
        <v-container grid-list-sm class="pa-4">
          <v-layout row wrap>
            <v-flex xs6>
                <v-text-field v-model="to"
                  placeholder="To"
                ></v-text-field>
            </v-flex>
            <v-flex xs6>
              <v-text-field v-model="subject"
                placeholder="Subject"
              ></v-text-field>
            </v-flex>
            <v-flex xs12>
              <v-text-field
                v-model="text"
                placeholder="Text"
                textarea
              ></v-text-field>
            </v-flex>
          </v-layout>
          <file-upload ref="upload"></file-upload>
        </v-container>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn flat color="primary" @click="dialog = false">Cancel</v-btn>
          <v-btn flat @click="dialog = false; send();">Send</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    </div>
    `,
    methods:
    {
        send: function()
        {
            uploadref = this.$refs.upload;
            var data = {
                    info:
                    {
                        to: this.to,
                        subject: this.subject,
                        text: this.text,
                        from: loggeduser,
                        atts: this.$refs.upload.uploadedFiles,
                        mailer: layout.mailer,
                        atthash: this.$refs.upload.currenthash
                    }
                };
                
            if (this.fwdinfo != null)
            {
                data.info["references"] = this.fwdinfo.references;
                data.info["inreplyto"] = this.fwdinfo.inreplyto;
            }
            
            $.ajax({
                url: 'api/emails/sendmail',
                type: 'POST',
                data: data
            }).then(function(data)
            {  
                var result = JSON.parse(data);
                layout.alertme(result.message,result.success);
                uploadref.reset();
            });             

        } 
    }
})

//group of emails (referenced)
Vue.component('mail-group',
{
    props: ['emails','subject'],
    data: function()
    {
        return {
            replymail: '',
            replyinput: '',
            includeorig: true,
            alerttext: '',
            alertcolor: ''
        }
    },
    template: `
    <v-expansion-panel expand>
    <v-expansion-panel-content v-for="email in emails" :key="email.id" lazy hide-actions>
    <div slot="header">
    <v-layout row>
    <v-flex>
     <b>{{email.from}}</b> to<b> {{email.to}}</b><br> Preview: {{email.preview}}
     </v-flex>
     <v-spacer></v-spacer>
     <v-btn icon @click.stop="callreply(email)" ><v-icon>reply</v-icon></v-btn><v-btn @click.stop="forward(email)" icon><v-icon>forward</v-icon></v-btn>
     </v-layout>    
     </div>
        <v-card>
            <v-card-text>
                  {{email.date}}       
              <v-flex ma-3>
            <span v-html="email.html"></span>
            <div v-for="att in email.atts"><v-btn @click="download(email.id, att)">{{att}}</v-btn></div>
          </v-flex>
        </v-card-text>
      </v-card>
    </v-expansion-panel-content>
    <v-container v-if="reply">
    <v-flex>
    <v-divider></v-divider>
    <v-text-field multi-line label="Reply" v-model="replyinput"></v-text-field>
    <v-btn @click.stop="sendreply()">SEND</v-btn>
    <v-checkbox
        label="Include Original Message"
        v-model="includeorig"
      ></v-checkbox>
    </v-flex>
    </v-container>
    </v-expansion-panel>`,
    computed:
    {
        reply()
        {
            return layout.reply;
        }
    },
    methods:
    {
        callreply: function(email)
        {
            layout.reply = !layout.reply;
            this.replymail = email;
        },
        download: function(id, att)
        {
            layout.download(id,att);
        },
        preview: function(text)
        {
            return text.substring(0,10);
        },
        sendreply: function()
        {
            var data = {
                info:
                {
                    inreplyto: this.replymail.messageid,
                    references: this.replymail.references,
                    to: this.replymail.from,
                    subject: "Re:" + this.subject,
                    text: this.replyinput + ((this.includeorig == true) ? "<br><br> <b>Reply to: </b><br><q>" + this.replymail.html + "</q>": ""),
                    from: loggeduser,
                    mailer: layout.mailer
                }
            };

            $.ajax({
                url: 'api/emails/sendmail',
                type: 'POST',
                data: data
            }).then(function(data)
            {

                var result = JSON.parse(data);
                layout.alertme(result.message,result.success);
            });
        },
        forward: function(email)
        {
            var info =
                {
                    inreplyto: email.messageid,
                    references: email.references,
                    subject: "Fwd:" + this.subject,
                    text: "<br><br> <b>Forwarded message: </b><br><q>" + email.html + "</q>",
                }

            layout.sendemail(info);
        }
    }
})
//top search field
Vue.component('search',
{
    data: function () {
        return {
            input: 'tag:inbox'
        }
      },
    template: `
    <v-layout row>
    <v-btn icon v-on:click="esearch(10)">
          <v-icon>search</v-icon>
      </v-btn>
      <v-text-field
        v-model="input" 
        flat
        solo-inverted
        label="Search"
        class="hidden-sm-and-down"
      ></v-text-field>
      </v-layout>
    `,
    methods:
    {
        esearch: function (currentlyLoaded)
        {
            if (loggeduser != '')
            {
                var data = { 
                        user : loggeduser,
                        query : this.input,
                        number : currentlyLoaded
                    };
                $.ajax({
                    url: 'api/elastic/search',
                    type: 'POST',
                    data: data
                }).then(function(data)
                {
                    result = JSON.parse(data);
                    if (result.success)
                    {
                        layout.showmail(result.groups);
                        layout.result = result.hitcount;
                    }
                    else
                    {
                        layout.emaillist = [];
                        layout.result = 'No match found';
                    }
                });
            }
        }
    },
    created: function () 
    {
        this.esearch(10);
        setInterval(function () {
         this.esearch(layout.currentlyLoaded);
        }.bind(this), 30000); 
    }
})

//header menu for mail
Vue.component('mail-menu',
{
    props: ['mailids', 'avtags', 'mailtags', 'mailsubject'],
    data: function () {
        return {
            menu: false,
        }
      },
    template: `
    <v-layout row>
    ({{numberofmails}})
    <div v-if="hasTag('unread')"><h1>{{mailsubject}}</h1></div>
    <div v-else>{{mailsubject}}</div>
    <v-spacer></v-spacer>
    <v-btn icon @click.stop="tagMail('unread',hasTag('unread'))"><v-icon>mail</v-icon></v-btn>
    <v-btn v-if="hasTag('trash')" icon @click.stop="tagMail('trash', true)"><v-icon>undo</v-icon></v-btn>
    <v-btn icon @click.stop="tagMail('trash',hasTag('trash'))"><v-icon v-if="hasTag('trash')">delete_forever</v-icon><v-icon v-else>delete</v-icon></v-btn>
    <v-menu offset-y v-model="menu">
    <v-btn icon slot="activator" @click.stop="menu = !menu"><v-icon>flag</v-icon></v-btn>
        <v-list>
        <v-list-tile :color="colorize(item.search)" v-for="item in realTags" :key="item.id" @click="tagMail(item.search,hasTag(item.search))">
        <v-list-tile-title>
          {{ item.text }}  
          </v-list-tile-title>
        </v-list-tile>
      </v-list>
    </v-menu>
    </v-layout>
    `,
    computed: {
        realTags: function()
        {
            return this.avtags.filter(function(u) {
                return u.issearch == "false";
            })
        },
        numberofmails: function()
        {
            return this.mailids.length;
        }
    },
    methods:
    {
        colorize: function(tag)
        {
            return this.mailtags.includes(tag) ? "green" : "default";
        },
        hasTag: function(tag)
        {
            return this.mailtags.includes(tag);

        },
        tagMail: function(tag,untag)
        {
            if (this.mailtags.includes('trash') && tag == "trash" && untag == false)
            {
                this.removeMail();
            }

            var mailids = this.mailids;
            var data = {
                ids: this.mailids,
                user: loggeduser,
                tag: tag,
                untag: untag
            };
            
            $.ajax({
                url: 'api/emails/changetags',
                type: 'POST',
                data: data
            }).then(function(tags)
            { 
                var data = {
                    tags: tags,
                    user: loggeduser,
                    ids: mailids
                };
                $.ajax({
                    url: 'api/elastic/updatetags',
                    type: 'POST',
                    data: data
                }).done(function(data)
                {
                    layout.$refs.searchinput.esearch(layout.currentlyLoaded);
                });
            });
        },
        removeMail: function()
        {
            var mailid = this.mailid;
            var data = {
                id: this.mailid,
                user: loggeduser
            };
            $.ajax({
                url: 'api/emails/removemail',
                type: 'POST',
                data: data
            }).then(function(data)
            {
               var data = {
                    user: loggeduser,
                    id: mailid
                };
                $.ajax({
                    url: 'api/elastic/removemail',
                    type: 'POST',
                    data: data
                }).done(function(data)
                {
                    layout.$refs.searchinput.esearch(layout.currentlyLoaded);
                });
            });
        }
    }
})

//menu for each mail group tags
Vue.component('tag-menu',
{
    props: ['tag'],
    data: function () {
        return {
            menu: false,
            inputicon: this.tag.icon,
            inputtext: this.tag.text,
            inputsearch: this.tag.search
        }
      },
    template: `
    <v-menu offset-y v-model="menu">
    <v-btn icon slot="activator" @click.stop="menu = !menu"><v-icon>mode_edit</v-icon></v-btn>
        <v-list two-line>
        <v-list-tile>
        <v-text-field label="Icon" @click.stop v-model="inputicon"></v-text-field>
        </v-list-tile>
        <v-list-tile>
        <v-text-field label="Text" @click.stop v-model="inputtext"></v-text-field>
        </v-list-tile>
        <v-list-tile v-if="tag.issearch == 'true'">
        <v-text-field label="Search"  @click.stop v-model="inputsearch"></v-text-field>
        </v-list-tile>
        <v-list-tile>
        <v-btn icon @click="adjustUserTag()"><v-icon>done</v-icon></v-btn><v-spacer></v-spacer><v-btn icon @click="removeUserTag()"><v-icon>delete</v-icon></v-btn>
        </v-list-tile>
      </v-list>
    </v-menu>
    `,
    methods:
    {
        removeUserTag: function()
        {
            var tag = this.tag;
            var data = {
                id: this.tag.search,
                user: loggeduser
            };
            $.ajax({
                url: 'api/client/removeusertag',
                type: 'POST',
                data: data
            }).then(function()
            {
                if (tag.issearch == "false")
                {
                    
                }
               layout.refreshTags();
            });
        },
        adjustUserTag: function()
        {
            var data = {
                id: this.tag.search,
                user: loggeduser,
                info: {
                    icon: this.inputicon,
                    text: this.inputtext,
                    search: this.inputsearch,
                    issearch: this.tag.issearch
                }
            };
            $.ajax({
                url: 'api/client/adjustusertag',
                type: 'POST',
                data: data
            }).then(function()
            {
               layout.refreshTags();
            });

        },
    }
})

//main part - parent component
var layout = new Vue({
    el: '#emails',
    data: 
    {
    passdialog: false,
    cpass:'',
    npass:'',
    cpassvis:true,
    npassvis:true,
    confirmrule: [() => ("The email and password you entered don\'t match")],
    mailer: "",
    mailerdialog: false,
    alert:
    {
        color: '',
        text: '',
        bar: false
    },
    currentlyLoaded: 10,
    emaillist: [],
    result: 'Total found',
    drawer: null,
    reply: false,
    items: [
        { icon: 'inbox', text: 'Inbox', search:"tag:inbox" },
        { icon: 'send', text: 'Sent', search:"tag:sent" },
        { icon: 'delete', text: 'Trash', search:"tag:trash" },
        {
        icon: 'keyboard_arrow_up',
        'icon-alt': 'keyboard_arrow_down',
        text: 'Tags',
        model: true,
        tags:[],
        calls: [
            { icon: 'add', text: 'Add tag', search:"call:addtag" },
            { icon: 'find_in_page', text: 'Add current search', search:"call:addsearch" }
        ]},
        { icon: 'date_range', text: 'Calendar', search:"call:calendar" },
        { icon: 'help', text: 'Help', search:"call:help" }
    ]
    },
    props: {
    source: String
    },
    computed:
    {
        matchingpass: function()
        {
            return this.cpass == this.npass;
        }
    },
    methods:
    {
        showmail: function(mailids)
        {
            var data = {
                ids: mailids,
                user: loggeduser
            };
            $.ajax({
                url: 'api/emails/getmail',
                type: 'POST',
                data: data
            }).then(function(data)
            { 
                var result = JSON.parse(data);
                if (result.success)
                {
                    layout.emaillist = result.groups;
                }
            });
        },
        changeDir: function(tag)
        {
            if (tag.search.split(":")[0] == "call")
            {
                newtag = "";
                newtext = "";
                $actiontag = false;
                switch (tag.search.split(":")[1])
                {
                    case "addtag":
                    {
                        newtext = "NewTag";
                    }
                    break;
                    case "addsearch":
                    {
                        newtag = this.$refs.searchinput.input;
                        newtext = "NewSearch";
                    }
                    break;
                    case "help":
                    {
                        actiontag = true;
                        this.alertme("BAZINGA! No help for you, yet...", false);
                    }
                    break;
                    case "calendar":
                    {
                        actiontag = true;
                        this.alertme("BAZINGA! No calendar for you, yet...", false);
                    }
                    break;
                }
                if (!actiontag)
                {
                    var data = { user: loggeduser , tag : newtag, text: newtext };
                    $.ajax({
                        url: 'api/client/addusertag',
                        type: 'POST',
                        data: data
                    }).then(function(data)
                    { 
                        layout.refreshTags();
                    });
                }
            }
            else
            {
                this.$refs.searchinput.input = (tag.issearch == "false") ? "tag:" + tag.text : tag.search;
                this.currentlyLoaded = 10;
                this.$refs.searchinput.esearch(this.currentlyLoaded);
            }
        },
        markRead: function(id)
        {
            this.$refs.mailmenu.forEach(foundmail => {
                if (foundmail.mailids.includes(id))
                    foundmail.tagMail("unread",true);
            });        
        },
        download: function(mailid, attName)
        {
            window.location = "api/emails/attachment/"+mailid+"/"+loggeduser+"/"+attName;
        },
        refreshTags: function()
        {
            var data = { user: loggeduser };

            $.ajax({
                url: 'api/client/getusertags',
                type: 'POST',
                data: data
            }).then(function(data)
            { 
                result = JSON.parse(data);
                layout.items[3].tags = [];
                result.forEach(function(tag) {
                    layout.items[3].tags.push(tag);
                });
            });
        },
        getSomeMore: function()
        {
            this.currentlyLoaded += 10;
            this.$refs.searchinput.esearch(this.currentlyLoaded);
        },
        alertme: function(text,success)
        {   
            this.alert.color = (success == true) ? 'success' : 'error';
            this.alert.text = text;
            this.alert.bar = true;
        },
        sendemail: function(info)
        {
            this.$refs.newmail.dialog = true;
            this.$refs.newmail.subject = info.subject;
            this.$refs.newmail.text = info.text;
            this.$refs.newmail.fwdinfo = info;
        },
        setmailer: function(mailer = '')
        {
            if (mailer != '')
            {
                this.mailer = mailer;
            }
            else
            {
                var data = { user : loggeduser, mailer: this.mailer};
                $.ajax({
                    url: 'api/client/setmailer',
                    type: 'POST',
                    data: data
                });
                this.alertme("Mailer was successfully changed to " + this.mailer, true);
            }
        },
        changepassword: function()
        {
            var data = { user : loggeduser, password: this.cpass };
                $.ajax({
                    url: 'api/client/changepassword',
                    type: 'POST',
                    data: data
                });
                this.alertme("Password successfully changed", true);
        }
    },
    mounted: function()
    {   
        this.refreshTags();
    }
})

}