import React from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@wedevs/tail-react';

type Props = {
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
};

const Pagination = ({ links }: Props) => {
  return links.map((link, index) => (
    <Button
      key={index}
      as={link.url ? Link : 'button'}
      variant="secondary"
      href={link.url as string}
      className="mr-2 mb-4 sm:mb-0 inline-block"
      disabled={!link.url || link.active}
    >
      <span dangerouslySetInnerHTML={{ __html: link.label }} />
    </Button>
  ));
};

export default Pagination;
