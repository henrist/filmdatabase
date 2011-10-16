var filmdata = {
	// data
	// genres
	// genres_count
	// actors
	
	active_filter: {
		title: null,
		genres_pos: [],
		genres_neg: [],
		year_active: false,
		year_type: "exact",
		year: "",
		year2: "",
		keywords: "",
		actors: null,
		type: null,
		dur_from: "",
		dur_to: ""
	},
	filmer: null,
	init: function()
	{
		this.filmer = $("filmer").getElement("tbody").getElements("tr");
		var self = this;
		
		// tittel
		var soketterf = function()
		{
			// endret seg?
			if (self.active_filter.title != this.get("value"))
			{
				self.active_filter.title = this.get("value");
				self.run_filter();
			}
		};
		$("soketter").addEvents({
			"keyup": soketterf,
			"change": soketterf
		});
		
		// nøkkelord
		var sokkeywf = function()
		{
			// endret seg?
			if (self.active_filter.keywords != this.get("value"))
			{
				self.active_filter.keywords = this.get("value");
				self.run_filter();
			}
		};
		$("sokkeywords").addEvents({
			"keyup": sokkeywf,
			"change": sokkeywf
		});
		
		// sett opp checkbokser for sjangre
		var p = [[
				1,
				$("genres_positive"),
				self.active_filter.genres_pos
			],[
				2,
				$("genres_negative"),
				self.active_filter.genres_neg
		]];
		
		var i = 0;
		p.each(function(g)
		{
			// sjangrene
			self.genres.each(function(genre)
			{
				new Element("label", {"for": "box"+g[0]+"_"+i, "text": genre})
					.grab(new Element("span", {"class": "genres_count", "text": self.genres_count[genre]})
						.grab(new Element("span", {"id": "genref_"+g[0]+"_"+genre, "class": "genre_filtered"}), "top"))
					.inject(
						new Element("input", {type: "checkbox", id: "box"+g[0]+"_"+i, "value": genre})
							.addEvent("click", function()
							{
								g[2][this.get("checked") ? "include" : "erase"](this.get("value"));
								self.run_filter();
								this.getParent(".genre_box")[this.get("checked") ? "addClass" : "removeClass"]("genre_checked");
							})
							.inject(
								new Element("span", {"class": "genre_box_inner"}).inject(
								new Element("span", {"class": "genre_box"}).inject(g[1]))),
						"after");
				i++;
			});
			
			// nullstill-knapp
			new Element("a", {"href": "#", "text": "Nullstill", "class": "genre_reset"}).addEvent("click", function(e)
			{
				e.stop();
				this.getParent(".genre_wrap").getElements("input").set("checked", false).each(function(el)
				{
					g[2].erase(el.get("value"));
					$$(".genre_box").removeClass("genre_checked");
				});
				self.run_filter();
			}).inject(g[1].getParent().getElement(".genre_pre"));
		});
		
		// årstall
		var yearf = function()
		{
			var t = this.get("id");
			var v = this.get("value").toInt();
			
			// ikke endret seg?
			if (self.active_filter[t] == v) return;
			self.active_filter[t] = v;
			
			// kjør filter om nødvendig
			self.check_year();
		};
		$$(".yearinput").addEvents({
			"keyup": yearf,
			"change": yearf
		});
		$$(".year_options input").each(function(elm)
		{
			elm.addEvent("click", function()
			{
				// ingen endring?
				if (elm.get("value") == self.active_filter.year_type) return;
				
				if (elm.get("value") == "between")
				{
					$("year2c").removeClass("hide");
					$($("year").get("value") != "" ? "year2" : "year").focus();
				}
				else
				{
					$("year2c").addClass("hide");
					$("year").focus();
				}
				
				self.active_filter.year_type = elm.get("value");
				
				// kjør filter om nødvendig
				self.check_year();
			});
		});
		
		// varighet
		var dur_fromf = function() {
			// endret seg?
			if (self.active_filter.dur_from != this.get("value")) {
				self.active_filter.dur_from = parseInt(this.get("value"));
				self.run_filter();
			}
		};
		$("dur_from").addEvents({
			"keyup": dur_fromf,
			"change": dur_fromf
		});
		
		var dur_tof = function() {
			// endret seg?
			if (self.active_filter.dur_to != this.get("value")) {
				self.active_filter.dur_to = parseInt(this.get("value"));
				self.run_filter();
			}
		};
		$("dur_to").addEvents({
			"keyup": dur_tof,
			"change": dur_tof
		});
		
		// kvalitet/type
		$$(".type_options input").addEvent("click", function(elm)
		{
			var n = [];
			$$(".type_options input[checked]").each(function(el){ n.push(el.get("value")); });
			
			if (n.length == 0) n = null;
			
			if (n == self.active_filter.type) return;
			self.active_filter.type = n;
			
			// kjør filter
			self.run_filter();
		});
		
		// funksjon for å vise/skjule kolonner
		function show_col(id, hide)
		{
			var elms = $("filmer").getElements("th:nth-child("+id+"),td:nth-child("+id+")");
			if (hide) elms.addClass("hide");
			else elms.removeClass("hide");
		}
		
		// stryr boksene for å vise/skjule kolonner
		$$("#enabled_cols input[type=checkbox]").each(function(elm)
		{
			var id = elm.get("id").substring(8);
			
			// events
			elm.addEvent("click", function()
			{
				show_col(id, !this.get("checked"));
			});
			
			// skjule?
			if (!elm.get("checked"))
			{
				show_col(id, true);
			}
		});
		
		// for å aktivere posters første gangen
		var posters_parsed = false;
		$("showcol_1").addEvent("click", function()
		{
			if (posters_parsed) return;
			posters_parsed = true;
			
			self.filmer.each(function(tr)
			{
				var td = tr.getElement("td");
				if (td.hasClass("noposter")) return;
				
				// legg til bildet
				new Element("img").set("src", "?poster="+tr.get("rel")).inject(td.empty());
			});
		});
		
		// vis feltene
		$("filterarea").removeClass("hide");
		$("setuparea").removeClass("hide");
		
		// sett fokus til tittelfeltet
		$("soketter").focus();
		
		// egen sortering for oppløsning og spilletid
		HtmlTable.Parsers.movie_resolution = {
			match: null,
			convert: function() {
				var x = this.get("text").split("x");
				if (x[1]) return x[0] * x[1];
				return 0;
			}
		};
		HtmlTable.Parsers.movie_rating = {
			match: null,
			convert: function() {
				return this.get("text").toFloat() || 0;
			}
		};
		HtmlTable.Parsers.movie_time = {
			match: null,
			convert: function() {
				return this.get("text").toInt() || 0;
			}
		};
		
		// aktiver sortering av tabellen
		this.sorter = new HtmlTable($("filmer"), {
			sortable: true,
			zebra: false,
			parsers: [null, "string", "number", "movie_rating", "movie_time", null, "movie_resolution", "string", "string", "string", "string", null]
		});
		this.sorter.zebra = false;
		this.sorter.addEvent("sort", function()
		{
			self.filmer = $("filmer").getElement("tbody").getElements("tr");
			self.zebra();
		});
		
		// sett opp skuespillere
		this.actors_list = [];
		for (key in this.actors)
		{
			this.actors_list.push({"name": key, "value": this.actors[key]});
		}
		
		new Meio.Autocomplete.Select("actor", this.actors_list, {
			//valueField:
			//valueFilter: function(data) { return data.name },
			filter: {
				type: "contains",
				path: "value"
			},
			minChars: 2,
			delay: 0,
			onSelect: function(elements, data)
			{
				self.active_filter.actors = [data["name"]];
				self.run_filter();
			},
			onDeselect: function(elements)
			{
				self.active_filter.actors = null;
				self.run_filter();
			}
		});
	},
	
	check_year: function()
	{
		// sjekk om vi skal kjøre filter
		var y = this.active_filter.year+"";
		var y2 = this.active_filter.year2+"";
		if (y.length == 4 && (y2.length == 4 || this.active_filter.year_type != "between"))
		{
			this.active_filter.year_active = true;
			this.run_filter();
		}
		
		// har vi filter aktivert?
		else if (this.active_filter.year_active)
		{
			this.active_filter.year_active = false;
			this.run_filter();
		}
	},
	
	run_filter: function()
	{
		$("filmer").setStyle("visibility", "hidden");
		
		var i = 0;
		var self = this;
		var alle = true;
		var genres_pos = this.active_filter.genres_pos.length > 0 ? this.active_filter.genres_pos : null;
		var genres_neg = this.active_filter.genres_neg.length > 0 ? this.active_filter.genres_neg : null;
		var genres_stats = {};
		this.filmer.each(function(tr)
		{
			// skal denne vises?
			var show = true;
			var data = self.data[tr.get("rel")];
			
			// type?
			if (self.active_filter.type)
			{
				if (!self.active_filter.type.contains(data["type"])) show = false;
			}
			
			// søke tittel?
			if (show && self.active_filter.title)
			{
				var value = self.active_filter.title.replace(/  */g, ".*");
				if (!data["title"].test(value, "i"))
				{
					show = false;
					
					// søk aka
					if (data["aka"]) data["aka"].each(function(val)
					{
						if (val.test(value, "i")) show = true;
					});
				}
			}
			
			// søk år?
			if (show && self.active_filter.year_active)
			{
				switch (self.active_filter.year_type)
				{
					case "exact":
						show = data["year"] == self.active_filter.year;
					break;
					
					case "before":
						show = data["year"] <= self.active_filter.year;
					break;
					
					case "after":
						show = data["year"] >= self.active_filter.year;
					break;
					
					case "between":
						show = data["year"] >= self.active_filter.year && data["year"] <= self.active_filter.year2;
					break;
				}
			}
			
			// søk varighet?
			if (show && self.active_filter.dur_from) {
				show = data["runtime"] >= self.active_filter.dur_from;
			}
			if (show && self.active_filter.dur_to) {
				show = data["runtime"] <= self.active_filter.dur_to;
			}
			
			// søk nøkkelord?
			if (show && self.active_filter.keywords)
			{
				var f = false;
				data.keywords.each(function(v)
				{
					if (v.test(self.active_filter.keywords, "i")) f = true;
				});
				if (!f) show = false;
			}
			
			// må inneholde sjanger?
			if (show && genres_pos)
			{
				for (var x = 0; x < genres_pos.length; x++)
				{
					if (!data.genres.contains(genres_pos[x]))
					{
						show = false;
						break;
					}
				}
			}
			
			// kan ikke inneholde sjanger?
			if (show && genres_neg)
			{
				for (var x = 0; x < genres_neg.length; x++)
				{
					if (data.genres.contains(genres_neg[x]))
					{
						show = false;
						break;
					}
				}
			}
			
			// søke skuespiller?
			// TODO: søke etter flere skuespillere
			if (show && self.active_filter.actors)
			{
				self.active_filter.actors.each(function(actor)
				{
					if (!data.actors.contains(actor)) show = false;
				});
			}
			
			if (show)
			{
				tr.removeClass("hide");
				if (++i % 2 == 1) tr.addClass("table-tr-odd");
				else tr.removeClass("table-tr-odd");
				
				// lagre sjangerstats
				data.genres.each(function(g)
				{
					if (!genres_stats[g]) genres_stats[g] = 0;
					genres_stats[g]++;
				});
			}
			
			else
			{
				tr.addClass("hide");
				alle = false;
			}
		});
		
		$("filmer").setStyle("visibility", "visible");
		
		// sett antall
		$("countsearch").set("text", alle ? "" : i+"/");
		
		// sett genre-antall
		this.update_genres(genres_stats);
		
		// marker som filtrert eller ikke
		$("filterarea")[alle ? "removeClass" : "addClass"]("filtered");
	},
	update_genres: function(stats)
	{
		this.genres.each(function(genre)
		{
			[1,2].each(function(i)
			{
				var x = stats[genre] ? stats[genre] : 0;
				$("genref_"+i+"_"+genre).set("text", x)
					.getParent(".genre_box")[x ? "removeClass" : "addClass"]("genre_none");
			});
		});
	},
	zebra: function()
	{
		var i = 0;
		this.filmer.each(function(tr)
		{
			if (tr.hasClass("hide")) return;
			if (++i % 2 == 1) tr.addClass("table-tr-odd");
			else tr.removeClass("table-tr-odd");
		});
	}
};