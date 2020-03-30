import json
import math
import re

######################################################################
########################## Read Three Files ##########################
######################################################################
class FileOperator:
	@staticmethod
	def readResults(filename):
		with open(filename) as json_file:
			data = json.load(json_file)
		return data

	@staticmethod
	def readQuery(filename):
		queries = []
		with open(filename) as query_file:
			lines = query_file.readlines()
		for line in lines:
			queries.append(line.rstrip("\n"))
		return queries

	@staticmethod
	def getFinal(finaldata):
		with open("./qn/hw1.csv", 'w') as file_output:
			for single in finaldata:
				file_output.write(single)

######################################################################
########################## Compares Results ##########################
######################################################################
class CompareResult:
	@staticmethod
	def convertURL(originalURL):
		temp = re.sub("(https://www\.)|(http://www\.)|(http://)|(https://)", "", originalURL)
		if temp.endswith("/"):
			temp = temp[:-1]
		return temp

	@staticmethod
	def compute(query, item_google, item_yahoo):
		item_map = {}
		parameters = []
		differs = percent = rho = 0.0
		n = 0
		for i in range(len(item_google[query])):
			item_map[CompareResult.convertURL(item_google[query][i])] = i
		for i in range(len(item_yahoo[query])):
			tempStr = CompareResult.convertURL(item_yahoo[query][i])
			if tempStr in item_map:
				substract = i - item_map[tempStr]
				differs += math.pow(substract, 2)
				n += 1
				# print("yahoo: " + str(i) + ", " + "google: " + str(item_map[item_yahoo[query][i]]) + " = " + str(differs))
		percent = round((n / 10) * 100, 1)
		if n > 1:
			rho = 1 - ((6 * differs) / (n * (math.pow(n, 2) - 1)))
		elif n == 1 and differs == 0:
			rho = 1.0
		parameters.append(n)
		parameters.append(percent)
		parameters.append(rho)
		return parameters

	@staticmethod
	def compare(query_set, item_google, item_yahoo):
		final_results = []
		total_n = total_percent = total_rho = total_differs = 0
		final_results.append("Queries, Number of Overlapping Results, Percent Overlap, Spearman Coefficient\n")
		for i in range(len(query_set)):
			query_result = CompareResult.compute(query_set[i], item_google, item_yahoo)
			final_results.append("Query " + str(i + 1) + ", " + str(query_result[0]) + ", " + str(query_result[1]) + ", " + str(query_result[2]) + "\n")
			total_n += query_result[0]
			total_percent += query_result[1]
			total_rho += query_result[2]
		average_n = total_n / 100
		average_percent = total_percent / 100
		average_rho = total_rho / 100
		final_results.append("Averages, " + str(average_n) + ", " + str(average_percent) + ", " + str(average_rho))
		return final_results

#############Driver code############
data_yahoo = FileOperator.readResults("./qn/hw1.json")
data_google = FileOperator.readResults("google_results.json")
query_set = FileOperator.readQuery("query.txt")
FileOperator.getFinal(CompareResult.compare(query_set, data_google, data_yahoo))
####################################
