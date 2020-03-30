import networkx as nx
import math

G = nx.read_edgelist("ExtractUrl/data/edgeList.txt", create_using=nx.DiGraph())

page_rank = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight', dangling=None)

with open("external_pageRankFile.txt", "w", encoding="utf-8") as output:
	for pg in page_rank:
		output.write("/Users/someone/solr-7.7.2/foxnews/" + pg + "=" + str(page_rank[pg]) + "\n")