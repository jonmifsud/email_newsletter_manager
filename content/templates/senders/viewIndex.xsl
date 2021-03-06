<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<form method="post" action="{$current-url}">
		<table class="selectable" data-interactive="data-interactive">
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">From Name</th>
					<th scope="col">From Email</th>
				</tr>
			</thead>
			<tbody>
				<xsl:if test="/data/senders/entry">
					<xsl:apply-templates select="/data/senders/entry"/>
				</xsl:if>
				<xsl:if test="not(/data/senders/entry)">
					<tr>
						<td class="inactive" colspan="4">
							<xsl:text>None found</xsl:text>
						</td>
					</tr>
				</xsl:if>
			</tbody>
		</table>
		<div class="actions">
			<fieldset class="apply inactive">
				<div>
					<select name="with-selected">
						<option value="">With Selected...</option>
						<option class="confirm" value="delete">Delete</option>
					</select>
				</div>
				<button name="action[apply]">Apply</button>
			</fieldset>
		</div>
	</form>
</xsl:template>

<xsl:template match="senders/entry">
	<tr>
		<td>
			<a href="{concat($root, '/symphony/extension/email_newsletter_manager/senders/edit/', handle)}"><xsl:value-of select="name"/></a>
			<input name="items[{id}]" type="checkbox" />
		</td>
		<td>
			<xsl:value-of select="current()//from-name"/>
		</td>
		<td>
			<xsl:value-of select="current()//from-address"/>
		</td>
	</tr>
</xsl:template>
</xsl:stylesheet>
