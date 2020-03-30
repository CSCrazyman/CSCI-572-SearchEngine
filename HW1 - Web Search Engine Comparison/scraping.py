from bs4 import BeautifulSoup
import time
import requests
import urllib.parse
import json
from random import randint
from html.parser import HTMLParser

USER_AGENT = {'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'}

class SearchEngine:
	@staticmethod
	def search(query, sleep=True):
		if sleep: # Prevents loading too many pages too soon
			time.sleep(randint(10, 100))
		query_sentence = '+'.join(query.split())
		url = "http://www.bing.com/search?q=" + query_sentence + "&count=30"
		soup = BeautifulSoup(requests.get(url, headers=USER_AGENT).text, "html.parser")
		results = SearchEngine.scrape_search_result(soup)
		return results

	@staticmethod
	def scrape_search_result(soup):
		raw_results = soup.select('li[class="b_algo"] h2 a')
		setOfResults = set()
		results = []
		count = 0
		#implement a check to get only 10 results and also check that URLs must not be duplicated
		for result in raw_results:
			if count == 10:
				break
			link = result.get('href')
			if link not in setOfResults:
				results.append(link)
				setOfResults.add(link)
				count += 1
		return results

class GenerateJSON:
	@staticmethod
	def generateJSON(input_file, output_file):
		data = {}
		with open(input_file) as file_input:
			lines = file_input.readlines()
		for line in lines:
			temp_str = line.rstrip('\n')
			print(temp_str)
			data[temp_str] = SearchEngine.search(temp_str)
		with open(output_file, 'w') as file_output:
			json.dump(data, file_output, indent=2)

#############Driver code############
GenerateJSON.generateJSON('query.txt', 'bing_results.json')
####################################