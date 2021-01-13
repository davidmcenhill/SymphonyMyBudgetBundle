<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Btg\MyBudgetBundle\Entity\Notes;

class HelpController extends Controller
{
	/*
	 * Extract the value between the given tag and attributes
	 * (could use googleapi for this too but seems like a bit of an overkill?)
	 * @param [IN] $tag : the tag of interest without < or >
	 * @param [IN] $attributes : attributes if any to match (other attributes are ignored)
	 * @param [IN] $in : string to look in
	 * @param [IN] $offset : offset into $in 
	 * @param [IN] $extent : number of chars in $in from $offset to look at.
	 * @return multidimensional array : for each match two elements where first is the value of interest,
	 * the second is the offset in $in to that value of interest.
	 */

	public function extractTag($tag, $attributes, $in, $offset = 0, $extent = null)
	{
		$matches = array();

		$pattern = "|<{$tag} ?{$attributes}[^>]*>(.*?)</{$tag}>|";

		if (\is_null($extent))
		{
			$status = \preg_match_all($pattern, $in, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE, $offset);
		}
		else
		{
			$input = \substr($in, $offset, $extent);
			echo "<br>" . \htmlentities($pattern) . ":" . \htmlentities($input) . '<br>';
			$status = \preg_match_all($pattern, $input, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
		}

		if ($status === false)
		{
			return false;
		}

		// To extract the value between the tags, we have the first group ie (.*?) which presumably means:
		// . = any character
		// * = 0 or more times
		// ? = optional so extract empty as no match
		// Returned in $matches[1]
		return $matches[1];
	}

	// String is in $matches[0][i][1] where i is the iterator:
	// Development notes action 
	public function notesAction(Request $request)
	{
		$author = 'DavidMcEnhill';

		$note = $this->getDoctrine()
			->getRepository('BtgMyBudgetBundle:Notes')
			->findOneBy(array('author' => $author));

		$debug_msg = '';

		$uri = 'http://www.yellowpages.com.au/search/listings?clue=Neo+Thai&locationClue=All+States&selectedViewMode=LIST&emsLocationId=';
		$page = \file_get_contents($uri);
		if ($page === FALSE)
		{
			$snippet = "Error reading page";
		}
		else if (\strlen($page) == 0)
		{
			$snippet = "No content";
		}
		else
		{
			$tag = "span";
			$attributes = 'id=\"listing.name.*\"';
			$matches = $this->extractTag($tag, $attributes, $page);

			if ($matches === false)
			{
				$snippet = 'No match in content';
			}
			else
			{
				//	\var_dump($matches);
				// Take the top 5 and their addresses:
				$cnt = 0;
				$suggestions = array();
				$results = '';

				foreach ($matches as $match)
				{
					$suggestion = array();
					$suggestion['name'] = $match[0];
					$addr_matches = $this->extractTag("span", 'class=\"address\"', $page, $match[1], 4096);
					$suggestion['address'] = '';
					if ($addr_matches !== false)
					{
						\var_dump($addr_matches);
						if ((\array_key_exists(0, $addr_matches) === true) && (\array_key_exists(0, $addr_matches[0]) === true))
						{
							$suggestion['address'] = $addr_matches[0][0];
						}
					}

					if (++$cnt > 5)
					{
						break;
					}

					$results .= $suggestion['name'] . ', ' . $suggestion['address'] . "<br />";
				}

				echo \htmlentities("<br>Results:$results</br>");
				$snippet = $results;
			}
		}
		$note = new Notes();
		$note->setNote($snippet);

		$form = $this->createFormBuilder($note)
			->add('note', 'textarea')
			->getForm();

		if ($request->getMethod() == 'POST')
		{
			$form->bindRequest($request);
			if ($form->isValid())
			{
				// Save to database.
				$em = $this->getDoctrine()->getEntityManager();
				$note->setDate(new \DateTime());
				$note->setAuthor($author);
				$em->persist($note);
				$em->flush();
				return $this->redirect($this->generateUrl('BtgMyBudgetBundle_help_notes'));
			}
		}

		return $this->render('BtgMyBudgetBundle:Help:notes.html.twig', array(
				'form' => $form->createView(),
				'debug_msg' => $debug_msg,
				'result_note' => $note->getNote())
		);
	}

}

