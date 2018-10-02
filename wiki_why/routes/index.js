var express = require('express');
var router = express.Router();
const rp = require('request-promise');
const cheerio = require('cheerio');

var stopwords = ["a", "about", "above", "after", "again", "against", "all", "am", "an", "and", "any","are","aren't","as","at","be","because","been","before","being","below","between","both","but","by","can't","cannot","could","couldn't","did","didn't","do","does","doesn't","doing","don't","down","during","each","few","for","from","further","had","hadn't","has","hasn't","have","haven't","having","he","he'd","he'll","he's","her","here","here's","hers","herself","him","himself","his","how","how's","i","i'd","i'll","i'm","i've","if","in","into","is","isn't","it","it's","its","itself","let's","me","more","most","mustn't","my","myself","no","nor","not","of","off","on","once","only","or","other","ought","our","ours","ourselves","out","over","own","same","shan't","she","she'd","she'll","she's","should","shouldn't","so","some","such","than","that","that's","the","their","theirs","them","themselves","then","there","there's","these","they","they'd","they'll","they're","they've","this","those","through","to","too","under","until","up","very","was","wasn't","we","we'd","we'll","we're","we've","were","weren't","what","what's","when","when's","where","where's","which","while","who","who's","whom","why","why's","with","won't","would","wouldn't","you","you'd","you'll","you're","you've","your","yours","yourself","yourselves"];

/* GET home page. */
router.get('/', function(req, res, next) {
  const options = {
    uri: `https://www.wikihow.com/Special:Randomizer`,
    transform: function (body) {
      return cheerio.load(body);
    }
  };
  rp(options)
  .then(($, result_list) => {
    heading = $('.firstHeading').text();
    method = $('#intro').children('p').toArray();
    for (var i = 0; i < method.length; i++) {
      if (method[i]['children']['0']['type'] == 'text'){
        intro = method[i]['children']['0']['data']
      }
    }
    intro_breakdown = intro.replace(/[.,\"/?#!$%\^&\*;:{}=\-_`~()]/g,"").split(" ")
    dict = {}
    for (var i = 0; i < intro_breakdown.length; i++) {
      if (stopwords.indexOf(intro_breakdown[i].toLowerCase()) == -1){
        if(!(intro_breakdown[i] in dict)){
          dict[intro_breakdown[i].toLowerCase()] = 1
        }
        else{
          dict[intro_breakdown[i]] = dict[intro_breakdown[i]]+=1
        }
      }
    }
    console.log(dict)
    keysSorted = Object.keys(dict).sort(function(a,b){return dict[a]-dict[b]})
    console.log(keysSorted);
    return res.render('index', {title: heading, body:intro});
  })

});

module.exports = router;
