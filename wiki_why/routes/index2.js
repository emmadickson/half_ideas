var express = require('express');
var router = express.Router();
const rp = require('request-promise');
const cheerio = require('cheerio');

/* GET home page. */
router.get('/', function(req, res, next) {
  var result_list = ''

  gender = ["woman", "man"];
  var randomGender = Math.floor((Math.random() * gender.length) + 0);
  console.log(gender[randomGender])
  const options = {
    uri: `https://www.wikihow.com/wikiHowTo?search=be+a+${gender[randomGender]}`,
    transform: function (body) {
      return cheerio.load(body);
    }
  };
  rp(options)
  .then(($, result_list) => {
    result_images = $('#searchresults_list').children('.result').children('.result_thumb').toArray();
    result_data = $('#searchresults_list').children('.result').children('.result_data').toArray();

    var image_list = []
    var article_links = []
    for (var i = 0; i < result_images.length; i++) {
      image_url = (result_images[i]['children']['1']['attribs']['src'])
      if (image_url.indexOf('/Be-') != -1){
        image_list.push(image_url)
      }
      article_link = (result_data[i]['children']['1']['attribs']['href'])
      if (article_link.indexOf('/Be-a') != -1){
        article_links.push(article_link.substring(article_link.indexOf('Be-a'), article_link.length))
      }
    }
    var randomImageIndex = Math.floor((Math.random() * image_list.length) + 0);
    randomImage = image_list[randomImageIndex]
    var randomArticleIndex = Math.floor((Math.random() * image_list.length) + 0);
    randomArticle = article_links[randomArticleIndex]
    return res.render('index', {title: randomArticle, image: randomImage});
  })

});

module.exports = router;
