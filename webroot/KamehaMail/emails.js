loggeduser = '';

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

                if (result.user)
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

//after logging, other components can be created
function allowothers()
{

//component for upper right dropdown account menu
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
        //action with account
        action: function(value)
        {
            switch(value)
            {
                case 'Logout':
                    this.logout();
                    break;
                case 'Set Mailer':
                    layout.mailerdialog = true;
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
                layout.alertme("Logout not successful", false);
            else
            {
                window.localStorage.removeItem('loginkey');
                window.location.href = "index.html";
            }
        });
        }
    }
})

//constants needed for file upload
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
            key: window.localStorage.getItem('loginkey'),
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
            url: 'api/emails/upload/' + window.localStorage.getItem('loginkey'),
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

//component for bottom right button for new email window
Vue.component('new-email',
{
    data: function () {
        return {
          to: '',
          subject: '',
          dialog: false,
          extrainfo: null,
          text: '',
          editoropts: { modules : { toolbar :   [['bold', 'italic', 'underline', 'strike'],  
          ['blockquote', 'code-block'],
        
          [{ 'header': 1 }, { 'header': 2 }],        
          [{ 'list': 'ordered'}, { 'list': 'bullet' }],
          [{ 'script': 'sub'}, { 'script': 'super' }],    
          [{ 'indent': '-1'}, { 'indent': '+1' }],    
          [{ 'direction': 'rtl' }],             
        
          [{ 'size': ['small', false, 'large', 'huge'] }],
          [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        
          [{ 'color': [] }, { 'background': [] }],        
          [{ 'font': [] }],
          [{ 'align': [] }],
        
          ['clean']] }}
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
            <quill-editor style="height: 450px" v-model="text" :options="editoropts"></quill-editor>
            <br>
            <br>
            </v-flex>
          </v-layout>
        </v-container>
        <v-card-actions>
          <file-upload ref="upload"></file-upload>
          <v-btn flat color="primary" @click="dialog = false; clearForm();">Cancel</v-btn>
          <v-btn flat @click="dialog = false; send();">Send</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    </div>
    `,
    methods:
    {
        clearForm: function()
        {
            this.to = '';
            this.subject = '';
            this.text = '';
            this.$refs.upload.reset();
        },
        send: function()
        {
            uploadref = this.$refs.upload;
            var data = {
                    info:
                    {
                        to: this.to,
                        subject: this.subject,
                        text: this.text,
                        atts: this.$refs.upload.uploadedFiles,
                        mailer: layout.mailer,
                        atthash: this.$refs.upload.currenthash
                    },
                    key: window.localStorage.getItem('loginkey')
                };
                
            if (this.extrainfo != null)
            {
                data.info["references"] = this.extrainfo.references;
                data.info["inreplyto"] = this.extrainfo.inreplyto;
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



//componentn for group of emails
Vue.component('mail-group',
{
    props: ['emails','subject'],
    template: `
    <v-expansion-panel expand>
    <v-expansion-panel-content v-for="email in emails" :key="email.id" lazy hide-actions>
    <div slot="header">
    <v-layout row>
    <v-flex>
     <b>{{email.from}}</b> to<b> {{email.to}}</b><br> Preview: {{email.preview}}
     </v-flex>
     <v-spacer></v-spacer>
     <v-btn icon @click.stop="reply(email)" ><v-icon>reply</v-icon></v-btn><v-btn @click.stop="forward(email)" icon><v-icon>forward</v-icon></v-btn>
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
    <v-container>
    <quill-editor style="height: 100px" v-model="replytext" :options="editoropts"></quill-editor>
    <br>
    <br>
    <br>
    <v-layout row>
    <v-btn @click.stop="quickreply()">QUICK REPLY</v-btn><v-spacer></v-spacer>
    <v-checkbox
        label="Include Original Message"
        v-model="includeorig"
      ></v-checkbox>
    </v-layout>
    </v-container>
    </v-expansion-panel>`,
    computed:
    {
        conversation()
        {
            return this.emails[this.emails.length - 1];
        }
    },
    data: function()
    {
        return {
            includeorig: true,
            alerttext: '',
            alertcolor: '',
            replytext: '',
            editoropts: { modules : { toolbar : [['bold', 'italic', 'underline', 'strike'],  
            ['blockquote', 'code-block'],
          
            [{ 'header': 1 }, { 'header': 2 }],            
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],     
            [{ 'indent': '-1'}, { 'indent': '+1' }],         
            [{ 'direction': 'rtl' }],                  
          
            [{ 'size': ['small', false, 'large', 'huge'] }], 
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
          
            [{ 'color': [] }, { 'background': [] }],        
            [{ 'font': [] }],
            [{ 'align': [] }],
          
            ['clean']] }}
        }
    },
    methods:
    {
        //download attachment
        download: function(id, att)
        {
            layout.download(id,att);
        },
        //direct reply from conversation
        quickreply: function()
        {
            var data = {
                info:
                {
                    inreplyto: this.conversation.messageid,
                    references: this.conversation.references,
                    to: (this.conversation.to.includes(loggeduser)) ? this.conversation.from : this.conversation.to,
                    subject: "Re:" + this.subject,
                    text: this.replytext + ((this.includeorig == true) ? "<br><br> <b>Reply to: </b><br>" + this.conversation.html : ""),
                    mailer: layout.mailer
                },
                key: window.localStorage.getItem('loginkey')
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
        //add info to new email component and open it
        forward: function(email)
        {
            var info =
                {
                    to:'',
                    inreplyto: email.messageid,
                    references: email.references,
                    subject: "Fwd:" + this.subject,
                    text: "<br><br> <b>Forwarded message: </b><br>" + email.html,
                }

            layout.sendemail(info);
        },
        //add info to new email component and open it
        reply: function(email)
        {
            var info =
                {
                    to: (this.conversation.to.includes(loggeduser)) ? this.conversation.from : this.conversation.to,
                    inreplyto: email.messageid,
                    references: email.references,
                    subject: "Re:" + this.subject,
                    text: "<br><br> <b>Reply to: </b><br>" + email.html,
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
            input: 'tag:inbox',
            took: ''
        }
      },
    template: `
    <v-layout row>
    <v-btn icon v-on:click="resetCurrent();esearch(10)">
          <v-icon>search</v-icon>
      </v-btn>
      <v-text-field
        v-model="input" 
        flat
        solo-inverted
        label="Search"
        class="hidden-sm-and-down"
      ></v-text-field>
      &ensp;{{ took }}
      </v-layout>
    `,
    methods:
    {
        resetCurrent: function()
        {
            layout.currentlyLoaded = 10;
        },
        esearch: function (currentlyLoaded)
        {
            var data = { 
                    key : window.localStorage.getItem('loginkey'),
                    query : this.input,
                    number : currentlyLoaded
                };
            $.ajax({
                url: 'api/elastic/search',
                type: 'POST',
                data: data
            }).then(data => 
            {
                var result = JSON.parse(data);
                if (result.success)
                {
                    layout.showmail(result.groups);
                }
                else
                {
                    layout.emaillist = [];
                }
                layout.alertme(result.message, result.success);
            });
        }
    },
    created: function () 
    {
        //call search every 30 seconds to refresh
        this.esearch(10);
        setInterval(function () {
         this.esearch(layout.currentlyLoaded);
        }.bind(this), 30000); 
    }
})

//header menu for each mailgroup
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
    <ul style="list-style-type:none">
    <li v-for="tag in realTags" v-if="hasTag(tag.search)">
        <font color="green">{{ tag.text }}</font>
    </li>
    </ul>
    <v-btn icon @click.stop="tagMail('unread',hasTag('unread'))"><v-icon>mail</v-icon></v-btn>
    <v-btn icon @click.stop="tagMail('archive',hasTag('archive'))"><v-icon v-if="hasTag('archive')">check_circle</v-icon><v-icon v-else>check_circle_outline</v-icon></v-btn>
    <v-btn v-if="hasTag('trash')" icon @click.stop="tagMail('trash', true)"><v-icon>undo</v-icon></v-btn>
    <v-btn v-if="hasTag('trash') === false" icon @click.stop="tagMail('trash',hasTag('trash'))"><v-icon >delete</v-icon></v-btn>
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
        //returns only tags, not calls
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
        //green if group has tag
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
            var mailids = this.mailids;
            var data = {
                ids: this.mailids,
                key: window.localStorage.getItem('loginkey'),
                tag: tag,
                untag: untag
            };
            
            $.ajax({
                url: 'api/emails/changetags',
                type: 'POST',
                data: data
            }).then(function(data)
            { 
                var result = JSON.parse(data);
                if (result.changed)
                {
                    var data = {
                        tags: result.tags,
                        key: window.localStorage.getItem('loginkey'),
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
                }
            });
        },
        //removes only latest mail of the group
        removeMail: function()
        {
            var mailid = this.mailid;
            var data = {
                id: this.mailid,
                key: window.localStorage.getItem('loginkey')
            };
            $.ajax({
                url: 'api/emails/removemail',
                type: 'POST',
                data: data
            }).then(function(data)
            {
               var data = {
                    key: window.localStorage.getItem('loginkey'),
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
                key: window.localStorage.getItem('loginkey')
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
                key: window.localStorage.getItem('loginkey'),
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
    //password dialog visibility and temp values
    passdialog: false,
    cpass:'',
    npass:'',
    cpassvis:true,
    npassvis:true,
    confirmrule: [() => ("The email and password you entered don\'t match")],
    //mailer dialog visibility and value
    mailer: "",
    mailerdialog: false,
    //alert values
    alert:
    {
        color: '',
        text: '',
        bar: false
    },
    //current number of loaded emails
    currentlyLoaded: 10,
    //current found emails
    emaillist: [],
    result: 'Total found',
    //open/closed left drawer
    drawer: null,
    reply: false,
    //items in left drawer
    items: [
        //general, for searching
        { icon: 'inbox', text: 'Inbox', search:"tag:inbox" },
        { icon: 'send', text: 'Sent', search:"tag:sent" },
        { icon: 'done', text: 'Archive', search:"tag:archive" },
        { icon: 'delete', text: 'Trash', search:"tag:trash" },
        {
        //tags menu
        icon: 'keyboard_arrow_up',
        'icon-alt': 'keyboard_arrow_down',
        text: 'Tags',
        model: true,
        tags:[],
        calls: [
            { icon: 'add', text: 'Add tag', search:"call:addtag" },
            { icon: 'find_in_page', text: 'Add current search', search:"call:addsearch" }
        ]},
    ]
    },
    props: {
    source: String
    },
    computed:
    {
        //check if new passwords match
        matchingpass: function()
        {
            return this.cpass == this.npass;
        }
    },
    methods:
    {
        //display mails info of given mail ids
        showmail: function(mailids)
        {
            var data = {
                ids: mailids,
                key: window.localStorage.getItem('loginkey')
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
        //new search
        changeDir: function(tag)
        {
            //calls 
            if (tag.search.split(":")[0] == "call")
            {
                newtag = "";
                newtext = "";
                actiontag = false;
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
                }
                if (!actiontag)
                {
                    var data = { key: window.localStorage.getItem('loginkey') , tag : newtag, text: newtext };
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
                //tags
                this.$refs.searchinput.input = (tag.issearch == "false") ? "tag:" + tag.text : tag.search;
                this.currentlyLoaded = 10;
                this.$refs.searchinput.esearch(this.currentlyLoaded);
            }
        },
        //tag mail as read
        markRead: function(id)
        {
            this.$refs.mailmenu.forEach(foundmail => {
                if (foundmail.mailids.includes(id))
                    foundmail.tagMail("unread",true);
            });        
        },
        //download attachment
        download: function(mailid, attName)
        {
            window.location = "api/emails/attachment/"+mailid+"/"+window.localStorage.getItem('loginkey')+"/"+attName;
        },
        //refresh tag menu for new/deleted tags to be displayed
        refreshTags: function()
        {
            var data = { key : window.localStorage.getItem('loginkey') };

            $.ajax({
                url: 'api/client/getusertags',
                type: 'POST',
                data: data
            }).then(function(data)
            { 
                result = JSON.parse(data);
                layout.items[4].tags = [];
                result.forEach(function(tag) {
                    layout.items[4].tags.push(tag);
                });
            });
        },
        //get more emails
        getSomeMore: function()
        {
            this.currentlyLoaded += 10;
            this.$refs.searchinput.esearch(this.currentlyLoaded);
        },
        //instead of classic alert
        alertme: function(text,success)
        {   
            this.alert.color = (success == true) ? 'success' : 'error';
            this.alert.text = text;
            this.alert.bar = true;
        },
        //forward data to child new email component
        sendemail: function(info)
        {
            this.$refs.newmail.dialog = true;
            this.$refs.newmail.subject = info.subject;
            this.$refs.newmail.text = info.text;
            this.$refs.newmail.extrainfo = info;
            this.$refs.newmail.to = info.to;
        },
        //set new mailer
        setmailer: function(mailer = '')
        {
            if (mailer != '')
            {
                this.mailer = mailer;
            }
            else
            {
                var data = { key: window.localStorage.getItem('loginkey'), mailer: this.mailer};
                $.ajax({
                    url: 'api/client/setmailer',
                    type: 'POST',
                    data: data
                });
                this.alertme("Mailer was successfully changed to " + this.mailer, true);
            }
        },
        //change password
        changepassword: function()
        {
            var data = { key: window.localStorage.getItem('loginkey'), password: this.cpass };
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
        var data = { key: window.localStorage.getItem('loginkey')};
        $.ajax({
            url: 'api/client/getmailer',
            type: 'POST',
            data: data
        }).then(function(data)
        { 
            layout.mailer = data;
        });
    }
})
}