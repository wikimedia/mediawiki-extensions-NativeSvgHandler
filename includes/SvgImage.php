<?php

class SvgImage extends MediaTransformOutput {

	public function __construct( $file, $url, $path = false, $parameters = [] ) {
		# Previous parameters:
		#   $file, $url, $width, $height, $path = false, $page = false

		$defaults = [
			'page' => false,
			'lang' => false
		];

		if ( is_array( $parameters ) ) {
			$actualParams = $parameters + $defaults;
		} else {
			# Using old format, should convert. Later a warning could be added here.
			$numArgs = func_num_args();
			$actualParams = [
				'width' => $path,
				'height' => $parameters,
				'page' => ( $numArgs > 5 ) ? func_get_arg( 5 ) : false
			] + $defaults;
			$path = ( $numArgs > 4 ) ? func_get_arg( 4 ) : false;
		}

		$this->file = $file;
		$this->url = $url;
		$this->path = $path;

		# These should be integers when they get here.
		# If not, there's a bug somewhere.  But let's at
		# least produce valid HTML code regardless.
		$this->width = round( $actualParams['width'] );
		$this->height = round( $actualParams['height'] );

		$this->page = $actualParams['page'];
		$this->lang = $actualParams['lang'];
	}

	public function toHtml( $options = [] ) {
		global $wgNativeImageLazyLoading;

		if ( count( func_get_args() ) == 2 ) {
			throw new MWException( __METHOD__ . ' called in the old style' );
		}

		$alt = empty( $options['alt'] ) ? '' : $options['alt'];

		$query = empty( $options['desc-query'] ) ? '' : $options['desc-query'];

		$attribs = [
			'alt' => $alt,
			'src' => $this->url,
			'decoding' => 'async',
		];

		if ( $options['loading'] ?? $wgNativeImageLazyLoading ) {
			$attribs['loading'] = $options['loading'] ?? 'lazy';
		}

		if ( !empty( $options['custom-url-link'] ) ) {
			$linkAttribs = [ 'href' => $options['custom-url-link'] ];
			if ( !empty( $options['title'] ) ) {
				$linkAttribs['title'] = $options['title'];
			}
			if ( !empty( $options['custom-target-link'] ) ) {
				$linkAttribs['target'] = $options['custom-target-link'];
			} elseif ( !empty( $options['parser-extlink-target'] ) ) {
				$linkAttribs['target'] = $options['parser-extlink-target'];
			}
			if ( !empty( $options['parser-extlink-rel'] ) ) {
				$linkAttribs['rel'] = $options['parser-extlink-rel'];
			}
		} elseif ( !empty( $options['custom-title-link'] ) ) {
			$title = $options['custom-title-link'];
			$linkAttribs = [
				'href' => $title->getLinkURL(),
				'title' => empty( $options['title'] ) ? $title->getFullText() : $options['title']
			];
		} elseif ( !empty( $options['desc-link'] ) ) {
			$linkAttribs = $this->getDescLinkAttribs(
				empty( $options['title'] ) ? null : $options['title'],
				$query
			);
		} elseif ( !empty( $options['file-link'] ) ) {
			$linkAttribs = [ 'href' => $this->file->getURL() ];
		} else {
			$linkAttribs = false;
			if ( !empty( $options['title'] ) ) {
				$attribs['title'] = $options['title'];
			}
		}

		if ( empty( $options['no-dimensions'] ) ) {
			$attribs['width'] = $this->width;
			$attribs['height'] = $this->height;
		}
		if ( !empty( $options['valign'] ) ) {
			$attribs['style'] = "vertical-align: {$options['valign']}";
		}
		if ( !empty( $options['img-class'] ) ) {
			$attribs['class'] = $options['img-class'];
		}
		if ( isset( $options['override-height'] ) ) {
			$attribs['height'] = $options['override-height'];
		}
		if ( isset( $options['override-width'] ) ) {
			$attribs['width'] = $options['override-width'];
		}

		return $this->linkWrap( $linkAttribs, Xml::element( 'img', $attribs ) );
	}
}
