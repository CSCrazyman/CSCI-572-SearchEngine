import com.opencsv.CSVReader;

import java.io.*;
import java.util.Map;
import java.util.Set;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.util.HashMap;
import java.util.HashSet;

public class ExtractLinks {

	private static final String INPUT_DIR = "./data/foxnews";
	private static final String MAP_FILE = "./data/URLtoHTML_fox_news.csv";
	private static final String OUTPUT_FILE = "./data/edgeList.txt";
	private static final String CHARSET = "UTF-8";
	
	public static void main(String[] args) throws Exception {
		Map<String, String> fileToUrl = new HashMap<>();
		Map<String, String> urlToFile = new HashMap<>();
		CSVReader csvReader = new CSVReader(new FileReader(MAP_FILE));
		
		/* Gets file(html)-url pairs */
		String[] row = null;
		while ((row = csvReader.readNext()) != null) {
			fileToUrl.put(row[0], row[1]);
			urlToFile.put(row[1], row[0]);
		}
		csvReader.close();
		
		/* Extracts */
		File directory = new File(INPUT_DIR);
		Set<String> edges = new HashSet<>();
		for (File file : directory.listFiles()) {
			Document document = Jsoup.parse(file, CHARSET, fileToUrl.get(file.getName()));
			Elements links = document.select("a[href]"); // a with href
			for (Element link : links) {
				String url = link.attr("abs:href").trim();
				if (urlToFile.containsKey(url)) {
					edges.add(file.getName() + " " + urlToFile.get(url));
				}
			}
		}
		
		/* Writes into edgeList.txt */
		PrintWriter writer = new PrintWriter(new FileOutputStream(new File(OUTPUT_FILE)));
		for (String edge : edges) {
			writer.println(edge);
		}
		writer.flush();
        writer.close();
	}

}
