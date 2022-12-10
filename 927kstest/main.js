function SkinPaper(startIndex = 0) {
  this.currentIndex = startIndex;
  this.tcicon = "https://img.alicdn.com/imgextra/i2/917298378/O1CN01X6zxhD2BlArEJekrd_!!917298378.jpg";
  this.boyicon =
    "https://img.alicdn.com/imgextra/i2/917298378/O1CN01PTPxxz2BlAwH1jVxH_!!917298378.png"
  this.girlicon =
    "https://img.alicdn.com/imgextra/i3/917298378/O1CN01KLUbG32BlAw8r7iRO_!!917298378.png";
  this.sex = this.girlicon;
  this.chatboxE = document.querySelector(".chatbox");
  this.listE = window.option_box.querySelector(".option_list");
  this.answerState = false;
  this.answerShow = true;
  this.settime = null;
  this.scrolltime = true;
  this.data = {};
  this.Paper = [
    { question: "你的性別是？", option: ["男", "女"], answer: null },
    {
      question: "用無名指輕拂眼下的皮膚，感覺到不平滑？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "眼尾出現細小的皺紋？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "笑的時候，眼下有放射狀的皺紋？",
      option: ["是", "不是"],
      answer: null,
    },
    { question: "眼角有小皺紋了？", option: ["是", "不是"], answer: null },
    {
      question: "眼下浮腫形成眼袋了？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "鼻子四周不再油油的？",
      option: ["是", "不是"],
      answer: null,
    },
    { question: "鼻翼的毛孔很粗了？", option: ["是", "不是"], answer: null },
    {
      question: "洗完臉拍上化妝水後，水份立刻被吸幹？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "感到只抹乳液仍不够滋潤？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "感到不得不使用乳液或乳霜？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "發現忽然冒出許多雀斑？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "常找不到合適洗面乳，洗完臉皮膚像塗了一層粉似的？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "臉上的毛孔越來越粗？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "臉頰的肉橫向生長，毛孔幾乎全看不到了？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "摸摸肌膚，覺得非常柔軟？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "鼻翼旁的笑紋似乎加深了？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "嘴巴四周覺得非常乾燥？",
      option: ["是", "不是"],
      answer: null,
    },
    { question: "嘴角似乎有些下垂？", option: ["是", "不是"], answer: null },
    {
      question: "天天洗臉，卻仍有污垢？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "不喜化妝，對皮膚保養亦不熱衷？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "粉底一定要用液態的或膏狀的，否則便不易上妝？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "雖不算胖，卻出現雙下巴？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "將頭髮向上挽起時，臉的輪廓顯得异常突出？",
      option: ["是", "不是"],
      answer: null,
    },
    { question: "感到臉色特別憔悴？", option: ["是", "不是"], answer: null },
    { question: "頸部出現皺紋了？", option: ["是", "不是"], answer: null },
    {
      question: "顴骨附近的肉往下移了？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "額頭有時出現一些小皺紋？",
      option: ["是", "不是"],
      answer: null,
    },
    {
      question: "測試結束！分析檢測中，請稍等...",
      option: [],
      answer: null,
      handler: () => {
        this.overHandler();
      },
      over: true,
    },
  ];
  window.over = this.Paper[this.Paper.length - 1].handler;
  this.copyCode = (ele) => {
    var _this = this;
    var alertobj = {
      question: "",
      option: ["立即添加Line獲取"],
      handler: () => {
        try {
          $$.goline();
        } catch (error) {}
      },
      over: true,
    };
    if (document.querySelector(".resultcode")) {
      var clipboard = new ClipboardJS(".resultcode");
      clipboard.on("success", (e) => {
        (ele.style.color = "#007100"),
          ele.nextElementSibling &&
            ((ele.nextElementSibling.innerText = "（複製成功）"),
            (ele.nextElementSibling.style.color = "red")),
          e.clearSelection();
        alertobj.question = "已成功複製，快去聯繫LINE客服領取結果吧";
        _this.Paper.push(alertobj);
        _this.outputQ(_this.Paper.length - 1);
        _this.tobottom();
      }),
        clipboard.on("error", (e) => {
          ele.nextElementSibling &&
            ((ele.nextElementSibling.innerText = "（複製失敗，請長按複製）"),
            (ele.nextElementSibling.style.color = "red")),
            (alertobj.question =
              "複製失敗，請長按複製，快去聯繫LINE客服領取結果吧");
          _this.Paper.push(alertobj);
          _this.outputQ(_this.Paper.length - 1);
          _this.tobottom();
        });
    }
  };
  this.reploadImg = function(imgUrl){
      var preloadLink = document.createElement("link");
      preloadLink.href = imgUrl;
      preloadLink.rel = "preload";
      preloadLink.as = "image";
      document.head.appendChild(preloadLink);
  }
  window.copyCode = this.copyCode;
  this.overHandler = function () {
    var _this = this;
    var submitobj = Object.create(null);
    _this.Paper.forEach((item) => {
      if (item.over) return;
      submitobj[item.question] = [item.answer];
    });
    var resobj = {
      question: "",
      option: ["立即添加Line獲取"],
      handler: () => {
        try {
          $$.goline();
        } catch (error) {}
      },
      over: true,
    };
    var conf = {
      type: "POST",
      url: "https://newworkbench.com/api/skintest",
      data: {
        origin: location.host,
        paper: JSON.stringify(submitobj),
      },
      success: function (res) {
        (res = JSON.parse(res)),
          setTimeout(() => {
            var code = res.code || "planttitle",
              themsg = `檢測成功!<br />你的檢測碼：<span id="copyCode" onclick="copyCode(this)" class="resultcode" data-clipboard-text="${code}">${code}</span><small>（點擊複製）</small><br />請將此「檢測碼」傳送給LINE客服領取「檢測結果」與「私人保養方案」`;
            resobj.question = themsg;
            _this.Paper.push(resobj);
            _this.outputQ(_this.Paper.length - 1);
            _this.tobottom();
          }, 4e3);
      },
      error: function (err) {
        console.log(err),
          setTimeout(() => {
            resobj.question =
              "檢測成功！檢測結果將通過LINE傳送給你，請添加LINE獲取。";
            _this.Paper.push(resobj);
            _this.outputQ(_this.Paper.length - 1);
            _this.tobottom();
          }, 1e3);
      },
    };
    $$.Ajax(conf);
  };
  this.questionNum = this.Paper.length;
  this.outputNowTime = function () {
    var nowtime = new Date();
    var start = parseInt(nowtime.valueOf().toString().slice(4, 9));
    count1.innerText = start;
    count2.innerText = (start % 10000) + 3593;
    setInterval(() => {
      var now = parseInt(count1.innerText) + 1;
      count1.innerText = now;
      count2.innerText = now % 10000;
    }, 10000);
    time.innerText = nowtime.getHours() + ":" + nowtime.getSeconds();
  };
  this.tobottom = function (now = document.documentElement.scrollTop) {
    var _this = this;
    var to_scrollTop = now + 20;
    var viewheight = document.documentElement.clientHeight;
    var currentTop = document.documentElement.scrollHeight;
    to_scrollTop + viewheight < currentTop
      ? (window.scrollTo(0, to_scrollTop),
        window.setTimeout(() => {
          _this.tobottom(to_scrollTop);
        }, 50))
      : window.scrollTo(0, currentTop);
  };
  this.outputA = function (text) {
    var _this = this;
    var msgE = window.msgTemplate.cloneNode(true);
    msgE.removeAttribute("id");
    msgE.classList.add("right");
    msgE.querySelector("img").src = _this.sex;
    msgE.querySelector(".msgtext").innerHTML = text;
    _this.chatboxE.insertAdjacentElement("beforeend", msgE);
  };
  this.outputQ = function (index = this.currentIndex) {
    var _this = this;
    if (_this.Paper.length <= index) {
      return;
    }
    var over = _this.Paper[index].over;
    var Qitem = _this.Paper[index];
    var msgE = window.msgTemplate.cloneNode(true);
    msgE.removeAttribute("id");
    msgE.classList.add("left");
    msgE.querySelector(".msgtext").innerHTML = Qitem.question;
    msgE.querySelector("img").src = this.tcicon;
    var options = Qitem.option;
    _this.chatboxE.insertAdjacentElement("beforeend", msgE);
    if (options.length === 0) {
      if (over) {
        Qitem.handler && Qitem.handler();
      } else {
        _this.outputQ(++index);
      }
      return;
    }

    _this.listE.innerHTML = "";
    options.forEach((opt, key) => {
      if (!opt) return;
      var liE = window.document.createElement("li");
      liE.innerHTML = opt;
      liE.addEventListener("click", function () {
        if (over) {
          Qitem.handler && Qitem.handler();
          return;
        }
        if (_this.data.answerState === false) return;
        _this.data.answerState = false;
        if (index === 0 && key === 0) {
          _this.sex = key === 0 ? _this.boyicon : _this.girlicon;
        }
        Qitem.answer = opt;
        _this.outputA(opt);
        _this.tobottom();
        setTimeout(() => {
          _this.outputQ(++index);
          _this.tobottom();
        }, 600);
      });
      over && (liE.className = "overbtn");
      options.length === 1 && (liE.style.width = "90%");
      _this.listE.insertAdjacentElement("beforeend", liE);
    });
    setTimeout(() => {
      _this.data.answerState = true;
    }, 500);
  };
  this.isEndQShow = () => {
    var targetE = this.chatboxE.lastElementChild;
    var visibleBottom = window.scrollY + document.documentElement.clientHeight;
    var visibleTop = window.scrollY;
    var centerY = targetE.offsetTop + targetE.offsetHeight / 2;
    if (centerY > visibleTop && centerY < visibleBottom) {
      this.data.answerShow = true;
    } else {
      this.data.answerShow = false;
    }
  };
  Object.defineProperty(this.data, "answerShow", {
    get: () => {
      return this.answerShow;
    },
    set: (newVal) => {
      if (this.answerShow === newVal) return;
      this.answerShow = newVal;
      if (this.answerShow === false) {
        this.answerState = false;
        window.option_box.style.display = "none";
        window.option_box.classList.remove("fadeTop");
      } else if (this.answerShow === true) {
        this.answerState = true;
        window.option_box.classList.add("fadeTop");
        window.option_box.style.display = "";
      }
    },
  });
  this.reploadImg(this.tcicon);
  this.reploadImg(this.boyicon);
  this.reploadImg(this.girlicon);
  this.outputNowTime();
  this.outputQ();
  this.$$time = setInterval(() => {
    if (typeof window.$$ === "undefined") return;
    clearInterval(this.$$time);
    $$.addEvent("scroll", () => {
      if (this.scrolltime) {
        this.scrolltime = false;
        this.isEndQShow();
        setTimeout(() => {
          this.scrolltime = true;
        }, 100);
      }
    });
  }, 500);
}
(function(){new SkinPaper()})();
