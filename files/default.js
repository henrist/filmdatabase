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
		this.filmer = $("#filmer tbody tr");
		var self = this;

		// tittel
		$("#soketter").on("keyup change", function()
		{
			// endret seg?
			if (self.active_filter.title != $(this).val())
			{
				self.active_filter.title = $(this).val();
				self.run_filter();
			}
		});

		// nøkkelord
		$("#sokkeywords").on("keyup change", function()
		{
			// endret seg?
			if (self.active_filter.keywords != $(this).val())
			{
				self.active_filter.keywords = $(this).val();
				self.run_filter();
			}
		});

		// sett opp checkbokser for sjangre
		var p = [[
				1,
				$("#genres_positive"),
				self.active_filter.genres_pos
			],[
				2,
				$("#genres_negative"),
				self.active_filter.genres_neg
		]];

		var i = 0;
		$(p).each(function(i, g)
		{
			// sjangrene
			$(self.genres).each(function(key, genre)
			{
				var id = "box"+g[0]+"_"+i,
				    id2 = "genref_"+g[0]+"_"+genre;

				$(g[1]).append(
					$('<label>', {for: id}).append(
						$('<span class="genre_box">').append(
							$('<span class="genre_box_inner">').append(
								$('<input type="checkbox">').attr("id", id).val(genre),
								document.createTextNode(genre),
								$('<span class="genres_count">').append(
									$('<span class="genre_filtered">').attr("id", id2),
									document.createTextNode(self.genres_count[genre])
								)
							)
						)
					)
				);

				i++;
			});

			// nullstill-knapp
			$(g[1]).parent().find(".genre_pre").append(
				$('<a href="#" class="genre_reset">Nullstill</a>').click(function() {
					$(this).closest(".genre_wrap").find("input").prop("checked", false).each(function()
					{
						$(".genre_box").removeClass("genre_checked");
					});
					g[2].length = 0;
					self.run_filter();
				})
			);

			$(g[1]).find('.genre_box input').click(function()
			{
				var val = $(this).val();
				if (this.checked) {
					g[2].push(val);
				} else {
					var i = g[2].indexOf(val);
					if (i != -1) {
						g[2].splice(i, 1);
					}
					/*g[2] = jQuery.grep(g[2], function(item) {
						return item != val;
					});*/
				}

				self.run_filter();
				$(this).parent(".genre_box")[this.checked ? "addClass" : "removeClass"]("genre_checked");
			});
		});

		// årstall
		$(".yearinput").on("keyup change", function()
		{
			var t = $(this).attr("id");
			var v = parseInt($(this).val());

			// ikke endret seg?
			if (self.active_filter[t] == v) return;
			self.active_filter[t] = v;

			// kjør filter om nødvendig
			self.check_year();
		});

		$(".year_options input").click(function()
		{
			// ingen endring?
			if ($(this).val() == self.active_filter.year_type) return;

			if ($(this).val() == "between")
			{
				$("#year2c").removeClass("hide");
				$($("#year").val() != "" ? "year2" : "year").focus();
			}
			else
			{
				$("#year2c").addClass("hide");
				$("#year").focus();
			}

			self.active_filter.year_type = $(this).val();

			// kjør filter om nødvendig
			self.check_year();
		});

		// varighet
		$("#dur_from").on("keyup change", function() {
			// endret seg?
			if (self.active_filter.dur_from != $(this).val()) {
				self.active_filter.dur_from = parseInt($(this).val());
				self.run_filter();
			}
		});

		$("#dur_to").on("keyup change", function() {
			// endret seg?
			if (self.active_filter.dur_to != $(this).val()) {
				self.active_filter.dur_to = parseInt($(this).val());
				self.run_filter();
			}
		});

		// kvalitet/type
		$(".type_options input").click(function() {
			var n = [];
			$(".type_options input:checked").each(function(){
				n.push($(this).val());
			});

			if (n.length == 0) n = null;

			if (n == self.active_filter.type) return;
			self.active_filter.type = n;

			// kjør filter
			self.run_filter();
		});

		// funksjon for å vise/skjule kolonner
		function show_col(id, hide)
		{
			var elms = $("#filmer th:nth-child("+id+"), #filmer td:nth-child("+id+")");
			if (hide) elms.addClass("hide");
			else elms.removeClass("hide");
		}

		// stryr boksene for å vise/skjule kolonner
		$("#enabled_cols input[type=checkbox]:not(:checked)").each(function()
		{
			var id = $(this).attr("id").substring(8);
			show_col(id, true);
		});
		$("#enabled_cols input[type=checkbox]").click(function() {
			var id = $(this).attr("id").substring(8);
			show_col(id, !this.checked);
		});

		// for å aktivere posters første gangen
		var posters_parsed = false;
		$("#showcol_1").click(function()
		{
			if (posters_parsed) return;
			posters_parsed = true;

			self.filmer.each(function()
			{
				var td = $(this).find("td").first();
				if (td.hasClass("noposter")) return;

				// legg til bildet
				td.empty().append($('<img>', {'data-src': "?poster="+$(this).attr("rel")}).unveil());
			});
		});

		// vis feltene
		$("#filterarea").removeClass("hide");
		$("#setuparea").removeClass("hide");

		// sett fokus til tittelfeltet
		$("#soketter").focus();

		// egen sortering for oppløsning og spilletid
		$.tablesorter.addParser({
			id: 'movie_resolution',
			is: function() { return false; },
			format: function(s) {
				var x = s.split('x');
				if (x[1]) return x[0] * x[1];
				return 0;
			},
			parsed: false,
			type: 'numeric'
		});
		$.tablesorter.addParser({
			id: 'movie_rating',
			is: function() { return false; },
			format: function(s) {
				return parseFloat(s) || 0;
			},
			parsed: false,
			type: 'numeric'
		});
		$.tablesorter.addParser({
			id: 'movie_time',
			is: function() { return false; },
			format: function(s) {
				return parseInt(s) || 0;
			},
			parsed: false,
			type: 'numeric'
		});

		$('#filmer').tablesorter();

		// sett opp skuespillere
		this.actors_list = jQuery.map(this.actors, function(key, val) {
			return {"name": key, "value": val};
		});

		/*
		FIXME
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
		});*/
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
		$("#filmer").css("visibility", "hidden");

		var i = 0;
		var self = this;
		var alle = true;
		var genres_pos = this.active_filter.genres_pos.length > 0 ? this.active_filter.genres_pos : null;
		var genres_neg = this.active_filter.genres_neg.length > 0 ? this.active_filter.genres_neg : null;
		var genres_stats = {};
		this.filmer.each(function()
		{
			// skal denne vises?
			var show = true;
			var data = self.data[$(this).attr("rel")];

			// type?
			if (self.active_filter.type)
			{
				if (jQuery.inArray(data['type'], self.active_filter.type) == -1) show = false;
			}

			// søke tittel?
			if (show && self.active_filter.title)
			{
				var value = self.active_filter.title.replace(/  */g, ".*");
				if (!data["title"] || !(new RegExp(value, 'i').test(data["title"])))
				{
					show = false;

					// søk aka
					if (data["aka"]) {
						$(data["aka"]).each(function()
						{
							if (new RegExp(value, 'i').test(this)) show = true;
						});
					}
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
				$(data.keywords).each(function()
				{
					if (new RegExp(self.active_filter.keywords, "i").test(this)) f = true;
				});
				if (!f) show = false;
			}

			// må inneholde sjanger?
			if (show && genres_pos)
			{
				for (var x = 0; x < genres_pos.length; x++)
				{
					if (jQuery.inArray(genres_pos[x], data.genres) == -1)
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
					if (jQuery.inArray(genres_neg[x], data.genres) != -1)
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
				$(self.active_filter.actors).each(function()
				{
					if (!jQuery.inArray(this, data.actors)) show = false;
				});
			}

			if (show)
			{
				i++;
				$(this).removeClass("hide");

				// lagre sjangerstats
				$(data.genres).each(function()
				{
					if (!genres_stats[this]) genres_stats[this] = 0;
					genres_stats[this]++;
				});
			}

			else
			{
				$(this).addClass("hide");
				alle = false;
			}
		});

		$("#filmer").css("visibility", "visible");

		// sett antall
		$("#countsearch").text(alle ? "" : i+"/");

		// sett genre-antall
		this.update_genres(genres_stats);

		// marker som filtrert eller ikke
		$("#filterarea")[alle ? "removeClass" : "addClass"]("filtered");
	},
	update_genres: function(stats)
	{
		$(this.genres).each(function(b, genre) {
			$([1,2]).each(function(a, i) {
				var x = stats[genre] ? stats[genre] : 0;
				$("#genref_"+i+"_"+genre).text(x).closest(".genre_box")[x ? "removeClass" : "addClass"]("genre_none");
			});
		});
	}
};