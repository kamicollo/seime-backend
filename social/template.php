<p><b>Bendra posėdžio statistika:</b></p>
<ul id="general-info">
		<li>Posėdis prasidėjo <?php echo date("H:i", strtotime($sitting->getStartTime())); ?> ir baigėsi <?php echo date("H:i", strtotime($sitting->getEndTime())); ?> (<?php echo $sitting->getLength(); ?> įskaitant pertraukas).
    <li>Pagal oficialią statistiką posėdyje dalyvavo <?php echo $c = $sitting->participation('participated'); ?> Seimo <?php echo __ending($c); ?> (<?php echo $sitting->participation('percentage'); ?>%).</li>
    <li>Pagal <a href="http://seime.lt" title="Seime.lt">Seime.lt</a> duomenis, Seimo nariai posėdyje dalyvavo <?php echo $sitting->participation('time-based'); ?>% laiko.</li>
    <li>Visuose posėdžio balsavimuose dalyvavo <?php echo $c = $sitting->getMemberStats('full-attendance'); ?> Seimo <?php echo __ending($c); ?>.</li>
    <li>Mažiau nei 30% laiko posėdyje dalyvavo <?php echo $c = $sitting->getMemberStats('short-attendance'); ?> Seimo <?php echo __ending($c); ?>.</li>
    <li>Posėdyje pasisakė <?php echo $c = $sitting->getMemberStats('speakers'); ?> Seimo <?php echo __ending($c); ?>.</li>
    <li>Posėdžio balsavimai, kurių rezultatas <em>pritarta</em>: <?php echo $sitting->getVotings('accepted'); ?> iš <?php echo $sitting->getVotings('all'); ?>.</li>
</ul>

<?php 
	$c = $sitting->getTopParticipants();
	if ( (count($c) > 0) && (count($c) <= 15) ) {
	?>
<p><b>Visuose posėdžio balsavimuose dalyvavo:</b></p>
<ul>
    <?php foreach($sitting->getTopParticipants() as $p) { echo "<li>$p</li>"; } ?>    
</ul>

<?php } ?>

<p><b>Mažiausiai laiko posėdyje dalyvavo:</b></p>
<ul>
    <?php foreach($sitting->getBottomParticipants() as $p) { echo "<li>$p</li>"; } ?>
</ul>

<p><b>Ilgiausiai posėdyje kalbėję Seimo nariai:</b></p>
<ul>
    <?php foreach($sitting->getTopSpeakers() as $p) { echo "<li>$p</li>"; } ?>    
</ul>

<h3>Svarbiausi nagrinėti klausimai</h3>
<?php foreach($sitting->getTopQuestions(3) as $question) { ?>
	<p><b><a href="<?php echo $question->getUrl(); ?>"><?php echo $question->getTitle(); ?></a></b></p>
	<ul>
		  <li>Klausimo svarstymo trukmė: <?php echo $c = round((strtotime($question->getEndTime()) - strtotime($question->getStartTime())) / 60,0); ?> <?php echo __ending($c, array('minučių', 'minutė', 'minutės')); ?>.</li>
    	<li>Svarstant šį klausimą Seimo nariai vidutiniškai dalyvavo <?php echo round($question->effective_presence * 100,0); ?>% laiko.</li>		  
		  <?php if ($c = $question->getPresenters()) { ?>
			<li>Klausimą pristatė: <?php 
					$presenters = array();
					foreach($c as $name => $id) {
						if (!empty($id)) $presenters[] = '<a href="http://seime.lt/' . getMemberLink($id) .'">' . $name . "</a>";
						else $presenters[] = $name;
					}
					echo implode(", ", $presenters)
				?>.
			</li>
			<?php } ?>
		  <li>Klausimo svarstymo metu pasisakė <?php echo $c = count($question->getSpeakers()); ?> Seimo <?php echo __ending($c); ?>.</li>
		  <? if ($last_voting = $question->getLastVoting()) { ?>
		  <li>Paskutinis balsavimas šiuo klausimu: <a href="<?php echo $last_voting->getUrl(); ?>"><?php echo $last_voting->getVotingTopic(); ?></a>. Balsavimo rezultatas: <?php echo ($last_voting->getVotingOutcome() == 'accepted') ? 'priimta' : 'atmesta'; ?> (<?php echo $last_voting->getVoting('accepted'); ?> balsai <em>už</em> iš <?php echo $last_voting->getVoting('present'); ?>).</li>
		  <?php } ?>
	</ul>
<?php } ?>
<h3>Daugiau informacijos</h3>
<ul>
    <li><a href="http://seime.lt/posedziai/<?php echo $sitting->getId(); ?>">Seime.lt posėdžio puslapis</a></li>
<li><a href="<?php echo $sitting->getUrl(); ?>">Oficialus posėdžio puslapis</a></li>
<li><a href="<?php echo $sitting->getUrl('transcript'); ?>">Posėdžio stenograma</a></li>
<li><a href="<?php echo $sitting->getUrl('protocol'); ?>">Posėdžio protokolas</a></li>
</ul>
