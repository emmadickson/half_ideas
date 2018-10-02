from flask import Flask
from flask import jsonify
import requests
import bs4
import operator
import re
import random

app = Flask(__name__, static_url_path='')

@app.route('/')
def render_index():

    stop_words=['and', 'a', 'the', 'is', 'to', 'can', 'in', 'your', "you're",
    'there', 'their', 'of', 'with', 'as', 'you', 'be', 'are', 'our', 'this',
    'get', 'what', 'for', 'an', 'on', 'or', 'have', 'whether', 'making', 'its',
    'most', 'o', 'there', 'it', 'some', 'here', 'them', 'that', 'all', 'we',
    'through', 'from', 'do', "it's", 'go', 'few', 'also', 'often']

    count = 0

    response = requests.get('https://www.wikihow.com/Special:Randomizer').text
    soup = bs4.BeautifulSoup(response, "html.parser")
    print(soup)
    intro = soup.find(id="intro").text
    front_index = intro.find('Q&A')
    back_index = intro.find('\n\n\n(adsbygoogle')
    all_steps = intro[front_index+4:back_index]

    for i in range(0, 2):
        wordlist = all_steps.split()
        wordfreq = []
        for w in set(wordlist):
            if w.lower() not in stop_words:
                wordfreq.append(w.lower())
        word_count = len(wordfreq)
        print(wordfreq)
        links = []

        while links == []:

            next_word = wordfreq[random.randint(0, len(word_count)-1)].lower()
            test_links=['https://www.wikihow.com/wikiHowTo?search={}'.format(next_word),
            'https://www.wikihow.com/wikiHowTo?search=be+{}'.format(next_word),
            'https://www.wikihow.com/wikiHowTo?search=be+a+{}'.format(next_word),
            'https://www.wikihow.com/wikiHowTo?search=make+{}'.format(next_word),
            'https://www.wikihow.com/wikiHowTo?search=cook+{}'.format(next_word)]
            for link in test_links:
                response = requests.get(link).text
                soup = bs4.BeautifulSoup(response, "html.parser")
                links = soup.findAll('a', attrs={'class': 'result_link'})
                if len(links) != 0:
                    print(next_word)
                    print(link)
                    break
                count = count + 1

        href=[]
        for link in links:
            if ':' not in link.get('href'):
                href.append(link.get('href'))
        link_link = 'https:{}'.format(href[i])
        print("link link")
        print(link_link)
        response = requests.get(link_link).text
        soup = bs4.BeautifulSoup(response, "html.parser")
        step = soup.findAll('div', attrs={'class': 'step'})
        all_steps = all_steps + '<br><p>'+str(step[random.randint(0, len(step)-1)].text.encode("utf8")) + '</p><br>'

        print(all_steps)
    return all_steps

if __name__ == '__main__':
    app.run()
